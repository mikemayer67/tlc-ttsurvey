<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

// perform all of the login valdation stuff
require(app_file('survey/ajax/validate.php'));
require_once(app_file('include/sendmail.php'));

start_ob_logging();

$userid = strtolower($_POST['userid']);

$old_name  = $_POST['old_name'];
$new_name  = $_POST['new_name'];
$old_email = $_POST['old_email'] ?: '';
$new_email = $_POST['new_email'] ?: '';

$changes = array();
if($old_name  !== $new_name ) { $changes['name']  = [$old_name, $new_name ]; }
if($old_email !== $new_email) { $changes['email'] = [$old_email,$new_email]; }

if($changes)
{
  if($old_email)
  {
    sendmail_profile($old_email,$userid,$changes);
  }
  if($new_email && ($new_email != $old_email))
  {
    sendmail_profile($new_email,$userid,$changes);
  }
}

end_ob_logging();

http_response_code(200);
regen_active_token();

die();
