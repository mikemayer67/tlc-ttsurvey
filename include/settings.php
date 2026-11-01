<?php
namespace tlc\tts;

use Nette\Utils\Strings;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));

class Settings {

  static private $defaults = [
    'app_name'        => 'Time and Talent Survey',
    'timezone'        => 'UTC',
    'is_dev'          => false,
    'admin_name'      => 'the survey admin',
    'pwreset_timeout' => 15,
    'pwreset_length'  => 10,
    'reminder_freq'   => 24, // hours
    'log_level'       => 0,  // error level logging on startup
    'smtp_auth'       => 1,  // 0=SMTPS, 1=STARTTLS
    'smtp_debug'      => 0,  // 0=None, 1=Server->Client, 2=Server<->Client, 3=extra
    'bug_reporting'   => 2,  // 0=disabled, 1=email only, 2=email + GitHub issue
    'summary_flags'   => 0,
  ];

  static private $values = array();

  static public function update(array $kv) {
    foreach($kv as $key=>$value) {
      self::set($key, $value);
    }
  }

  static public function validate(array $kv) 
  { 
    $errors = array();
    foreach($kv as $key=>$value) {
      $vfunc = "tlc\\tts\\validate_$key";
      if(function_exists($vfunc)) {
        $error = '';
        if(!$vfunc($value,$error)) { $errors[$key] = $error; }
      }
    }
    return $errors;
  }

  static public function default(string $key) : null|string|float 
  { 
    return self::$defaults[$key] ?? null; 
  }
  
  static public function raw(string $key) : ?string
  {
    if(key_exists($key,self::$values)) { return self::$values[$key]; }

    $value = MySQLFetchValue('select value from tlc_srv_settings where name=?','s',$key); 
    if( $value !== null && $value !== '' ) {
      self::$values[$key] = $value;
    }
    return $value;
  }

  static public function get(string $key) : null|string|float
  {
    if(key_exists($key,self::$values)) { return self::$values[$key]; }

    $value = MySQLFetchValue('select value from tlc_srv_settings where name=?','s',$key); 
    if( $value !== null && $value !== '' ) {
      self::$values[$key] = $value;
    } else {
      $value = self::$defaults[$key] ?? null; 
    }
    return $value;
  }

  static public function clear(string $key)
  {
    self::set($key,null);
  }

  static public function set(string $key,null|string|float $value) : void
  {
    if(is_null($value) || $value==='') {
      unset(self::$values[$key]);
      MySQLExecute('delete from tlc_srv_settings where name=?','s',$key);
    }
    else {
      MySQLExecute(
        "insert into tlc_srv_settings (name,value) values (?,?) on duplicate key update value=?",
        'sss',$key,$value,$value
      );
    }
  }
};

// fix timezone
date_default_timezone_set(Settings::get('timezone'));
MySQLExecute("SET time_zone = '".date('P')."'");

//
// Convenience Accessors
//

function get_setting(string $key)              { return Settings::get($key);      } 
function set_setting(string $key,mixed $value) { Settings::set($key,$value);      }
function clear_setting(string $key)            { Settings::clear($key);           } 
function setting_default(string $key)          { return Settings::default($key);  }

// App Look-and-Feel settings
function app_name()            { return get_setting('app_name'); }
function timezone()            { return get_setting('timezone'); }
function is_dev()              { return get_setting('is_dev'); }

// Admin settings
function admin_name()          { return get_setting('admin_name'); }
function admin_email()         { return get_setting('admin_email'); }
function primary_admin()       { return get_setting('primary_admin'); }

function admin_contact() {
  $contact = htmlspecialchars(admin_name(), ENT_QUOTES, 'UTF-8');
  $email = admin_email();
  if ($email) {
      $email_escaped = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
      $contact = sprintf("<a href='mailto:%s'>%s</a>", $email_escaped, $contact);
  }
  return $contact;
}

// Password reset settings
function pwreset_timeout()  { return get_setting('pwreset_timeout'); } // minutes
function pwreset_length()   { return min(20,max(4, get_setting('pwreset_length'))); }
function reminder_freq()    { return get_setting('reminder_freq'); } // hours

// Logging settings
function log_level()        { return get_setting('log_level'); }
function bug_reporting()    { return get_setting('bug_reporting'); }

// SMTP settings
function smtp_host()        { return get_setting('smtp_host'); }
function smtp_auth()        { return get_setting('smtp_auth'); }
function smtp_username()    { return get_setting('smtp_username'); }
function smtp_password()    { return get_setting('smtp_password'); }
function smtp_reply_email() { return get_setting('smtp_reply_email'); }
function smtp_reply_name()  { return get_setting('smtp_reply_name'); }
function smtp_debug()       { return get_setting('smtp_debug'); }

function smtp_port() { 
  $port = get_setting('smtp_port');
  if(!$port) {
    // if port is not set, infer it from smtp_auth
    //   STARTTLS => 587
    //   SMTPS    => 465
    $port = smtp_auth() ? 587 : 465;
  }
  return $port;
}

//
// Date and Time
//

date_default_timezone_set(timezone());

//
// Validation functions
//

function _fix_validate_value(?string &$value)
{
  if(isset($value)) { $value = trim($value); }
  else              { $value = ''; }
}

function validate_timezone(string $timezone,?string &$error=null) {
  $error = '';
  _fix_validate_value($timezone);
  if($timezone==='') { return true; }
  if(!date_default_timezone_set($timezone)) { 
    $error = "unrecognized timezone";
  }
  return strlen($error) == 0;
}

function validate_admin_name(string $name,?string &$error = null): bool {
  $error = '';
  _fix_validate_value($name);
  if($name==='') { return true; }
  if (!preg_match("/^[\p{L}\p{N} .'_-]+$/u", $name)) {
      $error = "Admin name contains invalid characters";
  }
  return strlen($error) == 0;
}

function validate_admin_email(string $email,?string &$error=null) {
  $error = '';
  _fix_validate_value($email);
  if($email==='') { return true; }
  if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "invalid email address"; 
  }
  return strlen($error) == 0;
}

function validate_pwreset_timeout(string|float $timeout,?string &$error=null) {
  $error = '';
  _fix_validate_value($timeout);
  if($timeout==='') { return true; }
  if(is_numeric($timeout)) {
    $timeout = 1*$timeout;
    if($timeout <= 0) { $error = "not a postive number"; }
  } else { 
    $error = "not a number";
  }
  return strlen($error) == 0;
}

function validate_pwreset_length(string|float $len,?string &$error=null) {
  $error = '';
  _fix_validate_value($len);
  if($len==='') { return true; }
  if(is_numeric($len)) {
    $len = 1*$len;
    if(!is_integer($len)) { $error = "not an integer"; }
    elseif($len<4)        { $error = "too small (<4)"; }
    elseif($len>20)       { $error = "too big (>20)"; }
  } else {
    $error = "not an integer";
  }
  return strlen($error) == 0;
}

function validate_smtp_host(string $host,?string &$error=null) {
  $error = '';
  _fix_validate_value($host);
  if( $host === '' ) { $error = "missing"; }
  elseif(!filter_var($host,FILTER_VALIDATE_DOMAIN)) {
    $error = "invalid domain name";
  }
  return strlen($error) == 0;
}

function validate_smtp_username(string $name,?string &$error=null) {
  $error = '';
  _fix_validate_value($name);
  if($name==='') { $error = 'missing'; }
  return strlen($error) == 0;
}

function validate_smtp_password(string $password,?string &$error=null) {
  $error = '';
  _fix_validate_value($password);
  if($password==='') { $error = 'missing'; }
  return strlen($error) == 0;
}

function validate_smtp_port(string|float $port,?string &$error=null) {
  $error = '';
  _fix_validate_value($port);
  if($port==='') { return true; }
  if(is_numeric($port)) {
    $port = 1*$port;
    if(!is_integer($port)) { $error = "not an integer"; }
    elseif($port<=0)       { $error = "not a positive integer"; }
  } else {
    $error = "not an integer";
  }
  return strlen($error) == 0;
}

function validate_smtp_reply_email(string $email,?string &$error=null) {
  $error = '';
  _fix_validate_value($email);
  if($email==='') { return true; }
  if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "invalid email address"; 
  }
  return strlen($error) == 0;
}
