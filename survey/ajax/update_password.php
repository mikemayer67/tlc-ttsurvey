<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require(app_file('survey/ajax/validate.php'));

require_once(app_file('include/users.php'));
require_once(app_file('include/validation.php'));
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

// validate current/old password
$old_pw_valid = $user->validate_password($old_pw);
$response['old_error'] = $old_pw_valid ? '' : 'Incorrect';

// validate new password
$error = '';
$new_pw_valid = adjust_and_validate_user_input('password',$new_pw,$error);
$response['new_error'] = $error;

if( $old_pw_valid && $new_pw_valid )
{
  // try to update the password
  if( !$user->set_password_quiet($new_pw) ) 
  {
    // sometihng unexpected went wrong...
    $response['success'] = false;
    $response['new_error'] = 'Failed to set new password';
  }
}
else 
{
  // there was a problem with either old or new password
  $response['success'] = false;
}

end_ob_logging();

regen_active_token();

echo json_encode($response);
die();
