<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/users.php'));

/**
 * Session cookies:
 *   - active user stores the userid of the logged in user
 *   - active token stores a temporary login vaidation token
 * Persistent cookies:
 *   - access tokens stores the cached user tokens (if requested)
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
 * LoginCookies is used to manage (well...) login cookies.
 *
 * LoginCookies supports both AJAX and noscript scenarios.
 *   In both cases, it is instantiated with the browser's current cookies
 *   For noscript:
 *   - any changes to the browser cookies are handled immediately
 *   - this must happen before any html is written to standard out
 *   For ajax:
 *   - any changes to the cookies are returned in an array
 *   - these will be passed back in the ajax response
 *   - the javascript that invoked ajax must set the cookies on the browser
 *
 * LoginCookies is a singleton class accessed via the instance() method.
 *   To support ajax, instance() should be passed a truthy value.
 *
 * AJAX SUPPORT IS CURRENTLY COMMENTED OUT... Its use would require
 *   dropping the httponly security guards on the access cookies.  I'm leaving
 *   it here in case I find an actual need for it. I'd rather have to uncomment
 *   some code than reimplement this rather hackish ajax workaround.
 **/

class LoginCookies
{
  private static $_instance = null;
// private $_ajax = false;
  private $_active_userid = '';
  private $_active_token = '';
  private $_access_tokens = array();

  public static function instance( /* $ajax=false */)
  {
    if(!self::$_instance) { self::$_instance = new LoginCookies(); }
//    if($ajax) { self::$_instance->_ajax = true; }
    return self::$_instance;
  }

  private function __construct()
  {
    // load the session level cookies

    $this->_active_userid = stripslashes($_COOKIE[ACTIVE_USER_COOKIE]??"");
    $this->_active_token  = stripslashes($_COOKIE[ACTIVE_TOKEN_COOKIE]??"");

    // manage/renew persistent cookies

    $this->_access_tokens = array();
    $tokens = stripslashes($_COOKIE[ACCESS_TOKENS_COOKIE]??"");
    $tokens = json_decode($tokens,true);
    if($tokens) {
      // remove any cached user access tokens that are no longer valid
      foreach( $tokens as $userid=>$token ) {
        if(validate_user_access_token($userid,$token))
        {
          $this->_access_tokens[$userid] = $token;
        }
      }
    }
    $tokens = json_encode($this->_access_tokens);
    $this->_set_cookie(ACCESS_TOKENS_COOKIE, $tokens, time() + 86400*365);
  }

  private function _set_cookie($key,$value,$expires)
  {  
//    if($this->_ajax)       { return array($key,$value,$expires);  } 
//    else if(isset($value)) { setcookie($key, $value, ['expires' => $expires,    ...SECURE_COOKIE_OPTIONS ]); }
    if(isset($value)) { setcookie($key, $value, ['expires' => $expires,    ...SECURE_COOKIE_OPTIONS ]); }
    else              { setcookie($key,'',      ['expires' => time()-3600, ...SECURE_COOKIE_OPTIONS ]); }
    return true;
  }

  // Active Userid and Access Token (session level)

  public function active_userid() { return $this->_active_userid; }
  public function active_token()  { return $this->_active_token;  }

  public function set_active_userid($userid,$token)
  {
    $this->_active_userid = $userid;
    $_SESSION['active-userid'] = $userid;
    $_SESSION['active-token'] = $token;
    return array(
      $this->_set_cookie(ACTIVE_USER_COOKIE,$userid,0),
      $this->_set_cookie(ACTIVE_TOKEN_COOKIE,$token,0)
    );
  }

  public function replace_active_token($token)
  {
    $_SESSION['active-token'] = $token;
    $this->_set_cookie(ACTIVE_TOKEN_COOKIE,$token,0);
  }

  public function clear_active_userid() { return $this->set_active_userid(null,null); }

  // Cached Access Tokens (persistent)

  public function access_tokens()       { return $this->_access_tokens;                  }
  public function access_token($userid) { return $this->_access_tokens[$userid] ?? null; }

  public function cache_token($userid,$token)
  {
    if($token) { $this->_access_tokens[$userid] = $token; } 
    else       { unset($this->_access_tokens[$userid]);   }

    return $this->_set_cookie( 
      ACCESS_TOKENS_COOKIE, 
      json_encode($this->_access_tokens), 
      time() + 86400*365*1.5,
    );
  }

  public function clear_token($userid)  { return $this->cache_token($userid,null); }
}

// login cookies convenience methods

function active_userid() { return LoginCookies::instance()->active_userid(); }
function active_token()  { return LoginCookies::instance()->active_token();  }
function access_tokens() { return LoginCookies::instance()->access_tokens(); }

// user login state

function logout_active_user() { return LoginCookies::instance()->clear_active_userid(); }

function start_survey_as($user)  // note that $user is a User instance
{
  $userid = $user->userid();
  $token  = gen_token();  // new session token
  return LoginCookies::instance()->set_active_userid( $userid, $token );
}

function resume_survey_as($userid,$user_token)
{
  $userid = strtolower($userid);

  if( !validate_user_access_token($userid,$user_token) ) { return false; }
  $session_token = gen_token();
  return LoginCookies::instance()->set_active_userid( $userid, $session_token );
}

function regen_active_token()
{
  $token = gen_token();
  LoginCookies::instance()->replace_active_token($token);
}

function remember_user_token($userid,$token)
{
  $userid = strtolower($userid);
  return LoginCookies::instance()->cache_token( $userid, $token );
}

function forget_user_token($userid)
{
  $userid = strtolower($userid);
  return LoginCookies::instance()->clear_token($userid);
}


