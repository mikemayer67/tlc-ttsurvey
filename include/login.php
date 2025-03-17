<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/users.php'));

const ACTIVE_USER_COOKIE = 'ttt-active-user';
const ACTIVE_TOKEN_COOKIE = 'ttt-active-token';
const CACHED_TOKENS_COOKIE = 'ttt-cached-tokens';

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
 **/

class LoginCookies
{
  private static $_instance = null;
  private $_ajax = false;
  private $_active_userid = null;
  private $_active_token = null;
  private $_cached_tokens = null;

  public static function instance($ajax=false)
  {
    if(!self::$_instance) { self::$_instance = new LoginCookies(); }
    if($ajax) { self::$_instance->_ajax = true; }
    return self::$_instance;
  }

  private function __construct()
  {
    $this->_active_userid = stripslashes($_COOKIE[ACTIVE_USER_COOKIE]??"");
    $this->_active_token  = stripslashes($_COOKIE[ACTIVE_TOKEN_COOKIE]??"");
    $tokens = stripslashes($_COOKIE[CACHED_TOKENS_COOKIE]??"");

    #reset cookie timeout
    setcookie(CACHED_TOKENS_COOKIE, $tokens, time() + 86400*365, '/');

    $this->_cached_tokens = array();
    $tokens = json_decode($tokens,true);
    if($tokens) {
      foreach( $tokens as $userid=>$token ) {
        if(validate_user_access_token($userid,$token))
        {
          $this->_cached_tokens[$userid] = $token;
        }
      }
    }
  }

  private function _set_cookie($key,$value,$expires)
  {  
    if($this->_ajax) { return array($key,$value,$expires);  } 
    else             { setcookie($key,$value,$expires,'/'); }
    return true;
  }

  // Active Userid and Access Token

  public function active_userid() { return $this->_active_userid; }
  public function active_token()  { return $this->_active_token;  }

  public function set_active_userid($userid,$token)
  {
    $this->_active_userid = $userid;
    return array(
      $this->_set_cookie(ACTIVE_USER_COOKIE,$userid,0),
      $this->_set_cookie(ACTIVE_TOKEN_COOKIE,$token,0)
    );
  }

  public function clear_active_userid() { return $this->set_active_userid(null,null); }

  // Cached Access Tokens

  public function cached_tokens()       { return $this->_cached_tokens;                  }
  public function access_token($userid) { return $this->_cached_tokens[$userid] ?? null; }

  public function cache_token($userid,$token)
  {
    if($token) { $this->_cached_tokens[$userid] = $token; } 
    else       { unset($this->_cached_tokens[$userid]);   }

    return $this->_set_cookie( 
      CACHED_TOKENS_COOKIE, 
      json_encode($this->_cached_tokens), 
      time() + 86400*365,
    );
  }

  public function clear_token($userid)  { return $this->cache_token($userid,null); }
}

function login_cookies() { return LoginCookies::instance(); }

// login cookies convenience methods

function active_userid() { return login_cookies()->active_userid(); }
function active_token()  { return login_cookies()->active_token();  }
function cached_tokens() { return login_cookies()->cached_tokens(); }

// user login state

function logout_active_user() { return login_cookies()->clear_active_userid(); }

function start_survey_as($user)  // note that $user is a User instance
{
  return login_cookies()->set_active_userid(
    $user->userid(),
    $user->access_token()
  );
}

function resume_survey_as($userid,$token)
{
  if( !validate_user_access_token($userid,$token) ) { return false; }
  return login_cookies()->set_active_userid( $userid, $token );
}

function remember_user_token($userid,$token)
{
  return login_cookies()->cache_token( $userid, $token );
}

function forget_user_token($userid)
{
  return login_cookies()->clear_token($userid);
}


