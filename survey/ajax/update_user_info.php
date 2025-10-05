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
$name   = $_POST['name'];
$email  = $_POST['email'];

$user = User::from_userid($userid);
if( !$user ) { 
  internal_error("Invalid userid: $userid");
}

$response = array('success'=>true);

$error = '';
$name_valid = adjust_and_validate_user_input('fullname',$name,$error);
$response['name'] = $name;
$response['name_error'] = $name_valid ? '' : $error;

$error = '';
$email_valid = adjust_and_validate_user_input('email',$email,$error);
$response['email'] = $email;
$response['email_error'] = $email_valid ? '' : $error;

if($name_valid && $email_valid) 
{
  if( $name !== $user->fullname() ) {
    $error = '';
    if( !$user->set_fullname_quiet($name,$error) ) {
      $response['success'] = false;
      $response['name_error'] = $error;
    }
  }
  if( $email !== $user->email() ) {
    $error = '';
    if( !$user->set_email_quiet($email,$error) ) {
      $response['success'] = false;
      $response['email_error'] = $error;
    }
  }
}
else
{
  $response['success'] = false;
}

end_ob_logging();

regen_active_token();

echo json_encode($response);
die();
