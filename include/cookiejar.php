<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/access_tokens.php'));

/**
 * Session cookies:
 *   - active user stores the userid of the logged in user
 *   - active token stores a temporary copy of the user's access token
 * Persistent cookies:
 *   - access tokens stores a cache of user access tokens (when requested)
 **/
const ACTIVE_USER_COOKIE = 'ttt-active-user';
const ACTIVE_TOKEN_COOKIE = 'ttt-active-token';
const ACCESS_TOKENS_COOKIE = 'ttt-access-tokens';

$config = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true);
$secure_cookies = (
  ($config['cookies_require_https'] ?? true) ||
  (($_SERVER['HTTPS'] ?? 'off') !== 'off')
);

define('SECURE_COOKIE_OPTIONS', [
  'path'     => '/',
  'domain'   => '',
  'secure'   => $secure_cookies,
  'httponly' => true,
  'samesite' => 'Lax',
]);

/**
 * CookieJar is used to manage login cookies.
 *   It is a singleton class that hides its singleton internally.
 *   All access to functionality is provided through public static methods
 **/
class CookieJar
{
  // the singleton
  private static ?CookieJar $_instance = null;

  // the singleton's attributes
  private string $_active_userid;
  private string $_active_token;
  private array  $_access_tokens;

  // thie singleton mechanics
  private static function instance()
  {
    if(!self::$_instance) { self::$_instance = new CookieJar(); }
    return self::$_instance;
  }

  private function __construct()
  {
    // load the session level cookies
    $this->_active_userid = $_COOKIE[ACTIVE_USER_COOKIE]  ?? "";
    $this->_active_token  = $_COOKIE[ACTIVE_TOKEN_COOKIE] ?? "";

    // manage/renew persistent cookies
    $tokens = $_COOKIE[ACCESS_TOKENS_COOKIE] ?? [];
    if($tokens) {
      $tokens = json_decode($tokens, true);
      $tokens = array_filter(
        $tokens,
        fn($token, $userid) => validate_access_token($userid, $token),
        ARRAY_FILTER_USE_BOTH
      );
    }
    $this->_access_tokens = $tokens;
    $this->_cache_access_tokens();
  }

  private function _set_session_cookie(string $key,string $value)
  {  
    setcookie($key, $value, ['expires' => 0, ...SECURE_COOKIE_OPTIONS ]); 
  }

  private function _clear_session_cookie(string $key)
  {
    setcookie($key,'', ['expires' => time()-3600, ...SECURE_COOKIE_OPTIONS ]);
  }

  private function _cache_access_tokens()
  {
    $expires = time() + 86400*550;
    setcookie(
      ACCESS_TOKENS_COOKIE,
      json_encode($this->_access_tokens),
      ['expires' => $expires, ...SECURE_COOKIE_OPTIONS ] // roughly 1.5 years
    );
  }

  // Active Userid and Access Token (session level)

  /**
   * Getter for the active userid cookie
   * @return string (empty string if no active userid)
   */
  public static function active_userid() : string
  { 
    $inst = self::instance();
    return $inst->_active_userid; 
  }

  /**
   * Getter for the active access token cookie
   * @return string (empty string if no active token)
   */
  public static function active_token() : string
  {
    $inst = self::instance();
    return $inst->_active_token;
  }

  /**
   * Updates the active userid and token cookies
   * @param string $userid
   * @param string $token
   * @param string $remember
   * @return void 
   */
  public static function set_active_userid(string $userid,string $token, bool $remember)
  {
    $inst = self::instance();
    $inst->_active_userid = $userid;
    $inst->_active_token  = $token;
    $inst->_set_session_cookie(ACTIVE_USER_COOKIE,$userid);
    $inst->_set_session_cookie(ACTIVE_TOKEN_COOKIE,$token);

    if($remember) {
      $inst->_access_tokens[$userid] = $token;
      $inst->_cache_access_tokens();
    }
  }

  /**
   * Replaces the active access token cookie with a new value
   * @param string $token 
   * @return void 
   */
  public static function update_active_token(string $token)
  {
    $inst = self::instance();
    $inst->_active_token = $token;
    $inst->_set_session_cookie(ACTIVE_TOKEN_COOKIE,$token);

    $userid = $inst->_active_userid;
    if( key_exists($userid, $inst->_access_tokens)) {
      $inst->_access_tokens[$userid] = $token;
      $inst->_cache_access_tokens();
    }
  }

  /**
   * Removes the active userid and access token cookies
   * @return void
   */
  public static function clear_active_userid() 
  {
    $inst = self::instance();
    $inst->_active_userid = "";
    $inst->_active_token = "";
    $inst->_clear_session_cookie(ACTIVE_USER_COOKIE);
    $inst->_clear_session_cookie(ACTIVE_TOKEN_COOKIE);
  }

  // Cached Access Tokens (persistent)

  /**
   * Returns all of the current access tokens
   * @return array ($userid=>$token)
   */
  public static function access_tokens() : array
  { 
    $inst = self::instance();
    return $inst->_access_tokens;
  }

  /**
   * Returns the current access token for the specified userid
   * @param mixed $userid 
   * @return null|string (null if there is no current token associated with the userid)
   */
  public static function access_token($userid) : ?string
  { 
    $inst = self::instance();
    return $inst->_access_tokens[$userid] ?? null; 
  }

  /**
   * Clears the access token associated with specified userid 
   * @param string $userid 
   * @return void 
   */
  public static function forget_access_token(string $userid)  
  { 
    $inst = self::instance();
    unset($inst->_access_tokens[$userid]);
    $inst->_cache_access_tokens();
  }
}

// login cookies convenience methods

function active_userid() { return CookieJar::active_userid(); }
function active_token()  { return CookieJar::active_token();  }
function access_tokens() { return CookieJar::access_tokens(); }
