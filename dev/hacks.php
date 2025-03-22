<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
@todo("Remove the entire dev directory");

// copy all _GET query options to _POST 
//   allows for testing of _POST only query options
//   allows for override of _POST query options
foreach( $_GET as $k=>$v ) 
{
  $_POST[$k] = $v;
}

if(key_exists('dev',$_GET)) {
  require(app_file('dev/dev.php'));
  die();
} 

if(key_exists('demo',$_GET)) {
  require(app_file('dev/demo.php'));
  die();
} 


