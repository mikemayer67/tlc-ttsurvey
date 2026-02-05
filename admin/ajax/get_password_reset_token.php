<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/sendmail.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-participants');

start_ob_logging();

$userid = strtolower($_POST['userid'] ?? '');
if(!$userid) { send_ajax_bad_request('missing userid'); }

$user = User::from_userid($userid);
if(!$user) { send_ajax_bad_request("invalid userid: $userid"); }

$token = $user->get_password_reset_token(true);
if(!$token) { send_ajax_internal_error('failed to create new token');}

$email = $user->email();

if($email) { sendmail_recovery($email,[$userid=>$token]); }

$response = new AjaxResponse();
$response->add('token',$token);
$response->add('email',$email);
$response->add('url',full_app_uri("p=pwreset"));

end_ob_logging();

$response->send();
die();


