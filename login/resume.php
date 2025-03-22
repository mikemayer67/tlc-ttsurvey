<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/login.php'));

$resume = $_POST['resume'] ?? null;
if(!$resume) { internal_error("Invalid resume request: $resume"); }

list($userid,$token) = explode(':',$resume);
if( resume_survey_as($userid,$token) ) {
  header("Location: ".app_uri());
}
else {
  echo "<h1>BAD RESUME ($userid,$token)</h1>";
}

die();
