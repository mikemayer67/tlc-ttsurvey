<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require(app_file('survey/ajax/validate.php'));

require_once(app_file('include/users.php'));
require_once(app_file('include/validation.php'));
require_once(app_file('include/cookiejar.php'));
require_once(app_file('include/ajax.php'));

start_ob_logging();

$userid = strtolower($_POST['userid']);
$name   = $_POST['name'];
$email  = $_POST['email'];

$user = User::from_userid($userid);
if( !$user ) { send_ajax_bad_request("Invalid userid: $userid"); }

$response = new AjaxResponse();

$error = '';
$name_valid = adjust_and_validate_user_input('fullname',$name,$error);
$response->add('name',$name);
$response->add('name_error',$name_valid ? '' : $error);

$error = '';
$email_valid = adjust_and_validate_user_input('email',$email,$error);
$response->add('email',$email);
$response->add('email_error',$email_valid ? '' : $error);

if($name_valid && $email_valid) 
{
  MySQLBeginTransaction();
  if( $name !== $user->fullname() ) {
    $error = '';
    if( !$user->set_fullname($name,$error) ) {
      // already validated the name, so there is no reason to be here...
      MySQLRollback();
      send_ajax_internal_error("Failed to update fullname: $error");
    }
  }
  if( $email !== $user->email() ) {
    $error = '';
    if( !$user->set_email($email,$error) ) {
      // already validated the email, so there is no reason to be here...
      MySQLRollback();
      send_ajax_internal_error("Failed to update email: $error");
    }
  }
  MySQLCommit();
}
else
{
  $response->fail();
}

end_ob_logging();

$response->send();
die();
