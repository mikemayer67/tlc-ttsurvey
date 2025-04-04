<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/login.php'));
require_once(app_file('include/settings.php'));

function dump($k,$v) {
  if(is_null($v)) { $v = "[null]"; }
  if($v==='') { $v = "''"; }
  if($v===false) { $v = '*false*'; }
  if($v===true) { $v = '*true*'; }
  echo "<pre>$k: ".print_r($v,true)."</pre>";
}

echo "<h2>Settings::Defaults</h2>";
$defaults = ['app_name','timezone','is_dev','admin_name','pwreset_timeout','pwreset_length',
  'log_level','smtp_auth','smtp_debug','junk','stuff'];

foreach($defaults as $key) {
  $value = setting_default($key);
  dump($key,$value);
}

echo "<h2>Settings::Functions</h2>";

$all_funcs = ['app_name', 'app_logo', 'timezone', 'is_dev', 'admin_name', 'admin_email', 
  'primary_admin', 'admin_contact', 'pwreset_timeout', 'pwreset_length', 'log_level', 
  'smtp_host', 'smtp_auth', 'smtp_username', 'smtp_password', 'smtp_reply_email', 
  'smtp_reply_name', 'smtp_debug', 'smtp_port'];

foreach($all_funcs as $f) {
  $f="tlc\\tts\\$f";
  $value = $f();
  dump($f,$value);
}

echo "<h2>Settings::Bogus</h2>";
foreach(['junk','stuff'] as $key) {
  $value = Settings::get($key);
  dump($key,$value);
}

echo "<h2>Settings::Update</h2>";
Settings::update('junk',8,'stuff','cow','smtp_debug',3,'oops','oopv');

foreach($all_funcs as $f) {
  $f="tlc\\tts\\$f";
  $value = $f();
  dump($f,$value);
}

foreach(['junk','stuff'] as $key) {
  $value = Settings::get($key);
  dump($key,$value);
}

echo "<h2>Settings::Clear</h2>";
Settings::clear('stuff');
Settings::set('junk',5);
Settings::set('oops','');
Settings::set('smtp_debug',0);

echo"<h2>Settings::set with array</h2>";
$data = ['junk'=>8, 'stuff'=>'cow', 'smtp_debug'=>3,'oops'=>'oopv'];
Settings::update($data);
foreach(['junk','stuff','oops','smtp_debug'] as $key) {
  $value = Settings::get($key);
  dump($key,$value);
}

Settings::clear('stuff');
Settings::set('junk',5);
Settings::set('oops','');
Settings::set('smtp_debug',0);

