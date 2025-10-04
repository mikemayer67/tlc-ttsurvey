<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require(app_file('survey/ajax/validate.php'));

require_once(app_file('include/users.php'));
require_once(app_file('include/login.php'));

validate_ajax_nonce('survey-form');

start_ob_logging();

$userid = $_POST['userid'];
$old_pw = $_POST['old_pw'];
$new_pw = $_POST['new_pw'];

$user = User::from_userid($userid);
if( !$user ) { 
  internal_error("Invalid userid: $userid");
}

$response = array('success'=>true, 'email'=>$user->email());
if( !$user->validate_password($old_pw) ) 
{
  $response['success'] = false;
  $response['error']   = 'Current password is incorrect';
}
elseif( !$user->set_password_no_email($new_pw) )
{
  $response['success'] = false;
  $response['error']   = 'New password is invalid';
} 

end_ob_logging();

regen_active_token();

echo json_encode($response);
die();
