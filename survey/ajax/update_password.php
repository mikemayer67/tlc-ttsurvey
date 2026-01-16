<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require(app_file('survey/ajax/validate.php'));

require_once(app_file('include/users.php'));
require_once(app_file('include/validation.php'));
require_once(app_file('include/login.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('survey-form');

start_ob_logging();

$userid = strtolower($_POST['userid']);
$old_pw = $_POST['old_pw'];
$new_pw = $_POST['new_pw'];

$user = User::from_userid($userid);
if( !$user ) { send_ajax_bad_request("Invalid userid: $userid"); }

$response = new AjaxResponse();
$response->add('email',$user->email());

// validate current/old password
$old_pw_valid = $user->validate_password($old_pw);
$response->add('old_error', $old_pw_valid ? '' : 'Incorrect');

// validate new password
$error = '';
$new_pw_valid = adjust_and_validate_user_input('password',$new_pw,$error);
$response->add('new_error',$error);

if( $old_pw_valid && $new_pw_valid ) {
  // try to update the password
  if( !$user->set_password($new_pw) ) {
    send_ajax_internal_error("Failed to set new password");
  }
} else {
  $response->fail();
}

end_ob_logging();

regen_active_token();
remember_user_token($userid, $user->regenerate_access_token() );

$response->send();
die();
