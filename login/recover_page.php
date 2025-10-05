<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));
require_once(app_file('include/redirect.php'));
require_once(app_file('login/elements.php'));

start_page('login');

$redirect_data = get_redirect_data();

$nonce = start_login_form("Recover Survey Login","recover");

$userid = $redirect_data['userid'] ?? null;
$email  = $redirect_data['email']  ?? null;

add_login_instructions([
  'Login recovery requires that you included an email address in your profile.',
  'Please enter either your userid or your email address.',
]);

add_login_input("userid", array(
  "value" => $userid,
  "optional" => True,
  "info" => login_info_html('recover-userid'),
));
add_login_input("email", array(
  "value" => $email,
  "optional" => True,
  "info" => login_info_html('recover-email'),
));

add_login_submit("Send Email",'recover',true);

close_login_form();
end_page();


die();
