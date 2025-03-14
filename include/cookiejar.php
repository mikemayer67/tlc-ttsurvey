<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

/* THIS FilE IS A WORK IN PROGRESS... COME BACK TO IT AFTER THE User CLASS IS WORKING */

/**
 * CookieJar is used to manage login cookies.
 *
 * CookieJar supports both AJAX and noscript scenarios.
 *   In both cases, it is instantiated with the browser's current cookies
 *   For noscript:
 *   - any changes to the browser cookies are handled immediately
 *   - this must happen before any html is written to standard out
 *   For ajax:
 *   - any changes to the cookies are returned in an array
 *   - these will be passed back in the ajax response
 *   - the javascript that invoked ajax must set the cookies on the browser
 *
 * CookieJar is a singleton class accessed via the instance() method.
 *   To support ajax, instance() should be passed a truthy value.
 **/
class CookieJar
{
  private static $_instance = null;
  private $_ajax = false;
  private $_active_userid = null;
  private $_active_token = null;
  private $_cached_tokens = null;

  public static function instance($ajax=false)
  {
    if(!self::$_instance) { self::$_instance = new CookieJar(); }

    if($ajax) {
      self::$_instance->_ajax = true;
    }

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

  public function get_active_userid()
  {
    return $this->_active_userid;
  }

  public function get_active_token()
  {
    return $this->_active_token;
  }

  private function _set_cookie($key,$value,$expires)
  {  
    if($this->_ajax) {
      return array($key,$value,$expires);
    } else {
      setcookie($key,$value,$expires,'/');
    }
    return true;
  }

  public function set_active_userid($userid,$token)
  {
    $this->_active_userid = $userid;
    return array(
      $this->_set_cookie(ACTIVE_USER_COOKIE,$userid,0),
      $this->_set_cookie(ACTIVE_TOKEN_COOKIE,$token,0)
    );
  }

  public function clear_active_userid()
  {
    return $this->set_active_userid(null,null);
  }

  public function get_access_token($userid)
  {
    return $this->_cached_tokens[$userid] ?? null;
  }

  public function set_access_token($userid,$token)
  {
    if($token) {
      $this->_cached_tokens[$userid] = $token;
    } else {
      unset($this->_cached_tokens[$userid]);
    }
    return $this->_set_cookie( 
      CACHED_TOKENS_COOKIE, 
      json_encode($this->_cached_tokens), 
      time() + 86400*365,
    );
  }

  public function clear_access_token($userid)
  {
    return $this->set_access_token($userid,null);
  }

  public function access_tokens()
  {
    return $this->_cached_tokens;
  }
}
