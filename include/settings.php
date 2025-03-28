<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));

function get_setting($key, $default=null) {
  $value = MySQLSelectValue('select value from tlc_tt_settings where name=?','s',$key);
  if(!isset($value)) { $value = $default; }
  return $value;
}

function set_setting($key,$value) {
  return MySQLExecute(
    "insert into tlc_tt_settings (name,value) values (?,?) on duplicate key update value=?",
    'sss',$key,$value,$value
  );
}

// App Look-and-Feel settings

function app_name()          { return get_setting('app_name',"Time and Talent Survey"); }
function set_app_name($v)    { return set_setting('app_name', $v); }

function app_logo()          { return get_setting('app_logo'); }
function set_app_logo($v)    { return set_setting('app_logo', $v); }

function app_timezone()      { return get_setting('app_tz','UTC'); }
function set_app_timeone($v) { return set_setting('app_tz', $v); }

function is_dev()            { return get_setting('is_dev',false); }
function set_dev($v)         { return set_setting('is_dev',$v); }

// Admin settings

function admin_name()          { return get_setting('admin_name','the survey admin'); }
function set_admin_name($v)    { return set_setting('admin_name', $v); }

function admin_email()         { return get_setting('admin_email'); }
function set_admin_email($v)   { return set_setting('admin_email', $v); }

function primary_admin()       { return get_setting('primary_admin'); }
function set_primary_admin($v) { return set_setting('primary_admin', $v); }

function admin_contact() {
  $contact = admin_name();
  $email = admin_email();
  if($email) { $contact = sprintf("<a href='mailto:%s'>%s</a>",$email,$contact); }
  return $contact;
}


// Password reset settings

function pwreset_timeout()       { return get_setting('pwreset_timeout', 900); }
function set_pwreset_timeout($v) { return set_setting('pwreset_timeout', $v);  }

function fix_pwreset_length($v)  { return min(20,max(4,$v)); }
function pwreset_length()        { return fix_pwreset_length(get_setting('pwreset_length', 10)); }
function set_pwreset_length($v)  { return set_setting('pwreset_length', fix_pwreset_length($v)); }

// Logging settings

function log_file()        { return get_setting('log_file', PKG_NAME.'.log'); }
function set_log_file($v)  { return set_setting('log_file', $v); }

function log_level()       { return get_setting('log_level', 2);  }
function set_log_level($v) { return set_setting('log_level', $v); }

// SMTP settings

function smtp_host()              { return get_setting('smtp_host'); }
function set_smtp_host($v)        { return set_setting('smtp_host', $v); }

function smtp_auth()              { return get_setting('smtp_auth', 1); }  // STARTTLS
function set_smtp_auth($v)        { return set_setting('smtp_auth', $v); }

function set_smtp_port($v)        { return set_setting('smtp_port', $v); }

function smtp_username()          { return get_setting('smtp_username'); }
function set_smtp_username($v)    { return set_setting('smtp_username', $v); }

function smtp_password()          { return get_setting('smtp_password'); }
function set_smtp_password($v)    { return set_setting('smtp_password', $v); }

function smtp_reply_email()       { return get_setting('smtp_reply_email'); }
function set_smtp_reply_email($v) { return set_setting('smtp_reply_email', $v); }

function smtp_reply_name()        { return get_setting('smtp_reply_name'); }
function set_smtp_reply_name($v)  { return set_setting('smtp_reply_name', $v); }

function smtp_debug()             { return get_setting('smtp_debug', 0); }
function set_smtp_debug()         { return set_setting('smtp_debug'); }

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


