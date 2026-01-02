<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/sendmail.php'));

validate_ajax_nonce('admin-participants');

start_ob_logging();

$userid = strtolower($_POST['userid']);

$user = User::from_userid($userid);
if($user) {
  $token = $user->get_password_reset_token(true);
  if($token) {
    $email = $user->email();

    if($email) { sendmail_recovery($email,[$userid=>$token]); }

    $response = array(
      'success'=>true, 
      'token'=>$token,
      'email'=>$email,
      'url'=>full_app_uri("p=pwreset"),
    );
  }
}
if(!$token) {
  $response = array('success'=>false, 'error'=>'No reset token generated');
}

end_ob_logging();

echo json_encode($response);
die();


