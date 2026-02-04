<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file("include/db.php"));

class AccessTokens
{
  // singleton pointer
  private static array $_users = array();

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

    self::$_users[$userid] = $this;
  }

  /**
   * Finds/creates the AccessTokens instance for specified userid
   * @param string $userid 
   * @return AccessTokens 
   */
  private static function lookup(string $userid) : AccessTokens
  {
    return self::$_users[$userid] ?? new AccessTokens($userid);
  }

  // Public static methods

  /**
   * Adds a new userid access token and updates the database
   * @param string $userid 
   * @param string $token 
   * @return bool 
   */
  public static function add(string $userid,string $token) : bool
  {
    return self::lookup($userid)->_add($token);
  }

  /**
   * Removes the specified token for the specified userid and udpates the database
   * @param string $userid 
   * @param string $token 
   * @return void 
   */
  public static function remove(string $userid,string $token)
  {
    self::lookup($userid)->_remove($token);
  }

  /**
   * Validates that the specified userid/token exists
   * @param string $userid 
   * @param string $token 
   * @return bool 
   */
  public static function validate(string $userid, string $token) : bool 
  {
    $user = self::lookup($userid);
    return in_array($token, $user->_tokens);
  }

  /**
   * Generates a new token for the specified userid
   * @param string $userid 
   * @return string the new token (empty on failure)
   */
  public static function generate(string $userid) : string
  {
    $new_token = gen_token();
    $rc = self::add($userid,$new_token);
    return $rc ? $new_token : '';
  }

  /**
   * Invalidates the specified token and generates a replacment
   * @param string $userid 
   * @param string $token to be invalidated
   * @return string the new token (empty on failure)
   */
  public static function regenerate(string $userid, string $old_token) : string
  {
    self::remove($userid, $old_token);
    return self::generate($userid);
  }

  // Private internal implementations of the public interface

  /**
   * @param string $token 
   * @return bool 
   */
  private function _add(string $token) : bool
  {
    $query = <<<MYSQL
      insert into tlc_tt_access_tokens (userid,token,expires)
      values (?,?,CURRENT_TIMESTAMP + INTERVAL 18 MONTH)
      on duplicate key update
        expires = CURRENT_TIMESTAMP + INTERVAL 18 MONTH
    MYSQL;
    $r = MySQLExecute($query,"ss",$this->_userid,$token);
    if($r === false) { return false; }

    $this->_tokens[] = $token;
    return true;
  }

  /**
   * @param string $token 
   * @return void 
   */
  private function _remove(string $token)
  {
    $query = <<<MYSQL
      delete from tlc_tt_access_tokens 
      where userid=? and token=?
    MYSQL;
    MySQLExecute($query,"ss",$this->_userid,$token);

    $tokens = $this->_tokens;
    $tokens = array_filter($tokens, fn($t) => $t !== $token);
    $this->_tokens = $tokens;
  }
}

// Convenience functions

/**
 * Validates the specified access token for the specified userid
 * @param mixed $userid 
 * @param mixed $token 
 * @return bool 
 */
function validate_access_token($userid,$token)
{
  $userid = strtolower($userid);
  return AccessTokens::validate($userid,$token);
}

/**
 * Generate a new access token for the specified userid
 * @param string $userid 
 * @return string 
 */
function generate_access_token(string $userid) : string
{
  $userid = strtolower($userid);
  return AccessTokens::generate($userid);
}

/**
 * Invalidates the specified token and generates a new one for the specified userid
 * @param string $userid 
 * @param string $token 
 * @return string the new token (empty on failure)
 */
function regenerate_access_token(string $userid,string $token) : string
{
  $userid = strtolower($userid);
  return AccessTokens::regenerate($userid,$token);
}

/**
 * Invalidates the specifed token for the specified user
 * @param string $userid 
 * @param string $token 
 * @return void 
 */
function remove_access_token(string $userid,string $token)
{
  $userid = strtolower($userid);
  AccessTokens::remove($userid,$token);
}
