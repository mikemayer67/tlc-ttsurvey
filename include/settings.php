<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));

class Settings {

  static private $defaults = [
    'app_name'        => 'Time and Talent Survey',
    'timezone'        => 'UTC',
    'is_dev'          => false,
    'admin_name'      => 'the survey admin',
    'pwreset_timeout' => 15,
    'pwreset_length'  => 10,
    'log_level'       => 2,
    'smtp_auth'       => 1,  // 0=SMTPS, 1=STARTTLS
    'smtp_debug'      => 0,  // 0=None, 1=Server->Client, 2=Server<->Client, 3=extra
  ];

  static private $values = array();

  static public function load_all()
  {
    $rows = MySQLSelectValues('select name,value from tlc_tt_settings');

    foreach($rows as $row) {
      $values[$row[0]] = $row[1];
    }
  }

  static public function update(...$kv) {
    if(count($kv) == 1) {
      $kv = $kv[0];
      foreach($kv as $k=>$v) {
        self::set($k,$v);
      }
    } else {
      while(count($kv)>1) {
        $k = array_shift($kv);
        $v = array_shift($kv);
        self::set($k,$v);
      }
    }
  }

  static public function default($key) 
  { 
    return self::$defaults[$key] ?? null; 
  }
  
  static public function get($key) 
  {
    if(key_exists($key,self::$values)) { return self::$values[$key]; }

    $value = MySQLSelectValue('select value from tlc_tt_settings where name=?','s',$key); 
    if(is_null($value) || $value==='') { 
      $value = self::$defaults[$key] ?? null; 
    }
    return $value;
  }

  static public function clear($key)
  {
    self::set($key,null);
  }

  static public function set($key,$value) 
  {
    if(is_null($value) || $value==='') {
      unset(self::$values[$key]);
      MySQLExecute('delete from tlc_tt_settings where name=?','s',$key);
    }
    else {
      MySQLExecute(
        "insert into tlc_tt_settings (name,value) values (?,?) on duplicate key update value=?",
        'sss',$key,$value,$value
      );
    }
  }
};

Settings::load_all();

function get_setting($key)        { return Settings::get($key);     } 
function set_setting($key,$value) { Settings::set($key,$value);     }
function clear_setting($key)      { Settings::clear($key);          } 
function setting_default($key)    { return Settings::default($key); }

// App Look-and-Feel settings
function app_name()            { return get_setting('app_name'); }
function app_logo()            { return get_setting('app_logo'); }
function timezone()            { return get_setting('timezone'); }
function is_dev()              { return get_setting('is_dev'); }

// Admin settings
function admin_name()          { return get_setting('admin_name'); }
function admin_email()         { return get_setting('admin_email'); }
function primary_admin()       { return get_setting('primary_admin'); }

function admin_contact() {
  $contact = admin_name();
  $email = admin_email();
  if($email) { $contact = sprintf("<a href='mailto:%s'>%s</a>",$email,$contact); }
  return $contact;
}

// Password reset settings
function pwreset_timeout()       { return get_setting('pwreset_timeout'); } // minutes
function pwreset_length()        { return min(20,max(4, get_setting('pwreset_length'))); }

// Logging settings
function log_level()       { return get_setting('log_level', 2);  }

// SMTP settings
function smtp_host()              { return get_setting('smtp_host'); }
function smtp_auth()              { return get_setting('smtp_auth'); }
function smtp_username()          { return get_setting('smtp_username'); }
function smtp_password()          { return get_setting('smtp_password'); }
function smtp_reply_email()       { return get_setting('smtp_reply_email'); }
function smtp_reply_name()        { return get_setting('smtp_reply_name'); }
function smtp_debug()             { return get_setting('smtp_debug'); }

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


