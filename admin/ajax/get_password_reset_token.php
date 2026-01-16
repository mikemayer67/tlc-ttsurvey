<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/sendmail.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-participants');

start_ob_logging();

$userid = strtolower($_POST['userid']);

// assume failure unless token was actually generated and sent
$response = new AjaxResponse(false);

$user = User::from_userid($userid);
if($user) {
  $token = $user->get_password_reset_token(true);
  if($token) {
    $email = $user->email();

    if($email) { sendmail_recovery($email,[$userid=>$token]); }

    $response->succeed();
    $response->add('token',$token);
    $response->add('email',$email);
    $response->add('url',full_app_uri("p=pwreset"));
  }
}

end_ob_logging();

$response->send();
die();


