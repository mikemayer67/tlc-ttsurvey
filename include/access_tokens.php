<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file("include/db.php"));

class AccessTokens
{
  // singleton pointer
  private static array $_instances = array();

  private string $_userid = '';
  private array  $_tokens = array();

  /**
   * AccessTokens constructor
   * AccessTokens are not constructed directly, but
   *   through the lookup function
   * @param string $userid 
   * @return void 
   */
  private function __construct(string $userid)
  {
    // perform cleanup of expired tokens
    //  needs to happen somewhere... here makes sense
    MySQLExecute('delete from tlc_tt_access_tokens where expires < CURRENT_TIMESTAMP');

    $tokens = MySQLSelectValues(
      "select token from tlc_tt_access_tokens where userid=?",
      "s",
      $userid
    );
    $this->_userid = $userid;
    $this->_tokens = $tokens ? $tokens : array();

    self::$_instances[$userid] = $this;
  }

  /**
   * Finds/creates the AccessTokens instance for specified userid
   * @param string $userid 
   * @return AccessTokens 
   */
  public static function lookup(string $userid) : AccessTokens
  {
    return self::$_instances[$userid] ?? new AccessTokens($userid);
  }

  /**
   * Adds a new token and updates the database
   * @param string $token 
   * @return bool to indicate success of adding new token
   */
  public function add(string $token) : bool
  {
    $query = <<<MYSQL
      insert into tlc_tt_access_tokens (userid,token,expires)
      values (?,?,CURRENT_TIMESTAMP + INTERVAL 18 MONTH)
      on duplicate key
        expires = CURRENT_TIMESTAMP + INTERVAL 18 MONTH
    MYSQL;
    $r = MySQLExecute($query,"ss",$this->_userid,$token);
    if($r === false) { return false; }

    $this->_tokens[] = $token;
    return true;
  }

  /**
   * Removes the specified token and udpates the database
   * @param string $token 
   * @return void 
   */
  public function remove(string $token)
  {
    $query = <<<MYSQL
      delete from tlc_tt_access_tokens 
      where userid=? and token=?
    MYSQL;
    MySQLExecute($query,"ss",$this->_userid,$token);

    $this->_tokens = array_filter($this->_tokens, fn($t) => $t !== $token);
  }

  /**
   * Validates that the specified token exists
   * @param string $token 
   * @return bool 
   */
  public function validate(string $token) : bool
  {
    return in_array($token,$this->_tokens);
  }

  /**
   * Generates a new token
   * @return string the new token (empty on failure)
   */
  public function generate() : string
  {
    $new_token = gen_token();
    $rc = $this->add($new_token);
    return $rc ? $new_token : '';
  }

  /**
   * Invalidates the specified token and generates a replacment
   * @param string $token to be invalidated
   * @return string the new token (empty on failure)
   */
  public function regenerate(string $old_token) : string
  {
    $this->remove($old_token);
    return $this->generate();
  }
}


/**
 * Validates the specified access token for the specified userid
 * @param mixed $userid 
 * @param mixed $token 
 * @return bool 
 */
function validate_access_token($userid,$token)
{
  $userid = strtolower($userid);
  $tokens = AccessTokens::lookup($userid);
  return $tokens->validate($token);
}

/**
 * Invalidates the specified token and generates a new one for the specified userid
 * @param mixed $userid 
 * @param mixed $token 
 * @return string the new token (empty on failure)
 */
function regenerate_access_token($userid,$token) : string
{
  $userid = strtolower($userid);
  $tokens = AccessTokens::lookup($userid);
  return $tokens->regenerate($token);
}
