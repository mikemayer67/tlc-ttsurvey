<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));
require_once(app_file('admin/surveys/create_new.php'));
require_once(app_file('admin/surveys/update.php'));

function dump($k,$v) {
  if(is_null($v)) { $v = "[null]"; }
  if($v==='') { $v = "''"; }
  if($v===false) { $v = '*false*'; }
  if($v===true) { $v = '*true*'; }
  echo "<pre>$k: ".print_r($v,true)."</pre>";
}

foreach(Surveys::_ancestors(2,2) as [$id,$rev]) {
  dump($id,$rev);
}

echo "ok";
