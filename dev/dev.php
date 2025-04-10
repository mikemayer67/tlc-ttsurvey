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

foreach(['mikemayer67','kitkat15','shadowcat','junk'] as $userid) {
  dump($userid,user_roles($userid));
}
