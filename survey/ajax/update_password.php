<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/login.php'));

log_dev("PHP_SAPI: ".print_r(PHP_SAPI,true));

validate_ajax_nonce('survey-form');

start_ob_logging();

$userid = $_POST['userid'];
$old_pw = $_POST['old_pw'];
$new_pw = $_POST['new_pw'];

// Validate that the login info on the client (cookie) matches the
//   login info on the server (session).
// Validate that the login info in the AJAX call matches the session login.
//
// If any failure occurs, present the ambiguous 405 API error page to obfuscate failure reason.

$active_userid = active_userid();
$session_userid = $_SESSION['active-userid'] ?? '(none)';
$active_token = active_token();
$session_token = $_SESSION['active-token'] ?? '(none)';
if( $session_userid !== $active_userid ) {
  log_warning("Invalid userid in password reset attempt: session=$session_userid, cookie=$active_userid");
  http_response_code(405);
  die();
}
if( $session_token !== $active_token ) {
  log_warning("Invalid token in password reset attempt: session=$session_token, cookie=$active_token");
  http_response_code(405);
  die();
}
if($userid !== $session_userid) {
  log_warning("Invalid userid in password reset attempt: session=$session_userid, ajax=$userid");
  http_response_code(405);
  die();
}

$user = User::from_userid($userid);
if( !$user ) { 
  internal_error("Invalid userid: $userid");
}

$response = array('success'=>true);
$error = '';
if( !$user->validate_password($old_pw) ) {
  $response = array('success'=>false, 'error'=>'Current password is invalid');
}
if($response['success']) {
  if($user->set_password_no_email($new_pw)) {
    log_dev("set_password_no_email returned");
    regen_active_token();
    
    log_dev("sapi name: ".php_sapi_name());
    if( function_exists('fastcgi_finish_request') ) {
      log_dev("fastcgi_finish_request found");
      echo json_encode($response);
      fastcgi_finish_request();
      $user->send_set_password_email();
      end_ob_logging();
      log_dev("fastcgi_finish_request done : email sent");
      die();
    }
    else
    {
      log_dev("synchronous send_set_password_email");
      $user->send_set_password_email();
      log_dev("emai sent");
    }
  } 
  else
  {
    $response = array('success'=>false, 'error'=>'New password is invalid');
  }
}

end_ob_logging();

log_dev("returning response: ".print_r($response,true));
echo json_encode($response);
die();
