<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

// don't drop the nonce as this may be required multiple times from same user menu
validate_and_retain_get_nonce('update-page');

require_once(app_file('include/users.php'));
require_once(app_file('include/redirect.php'));
require_once(app_file('login/elements.php'));

$redirect_data = get_redirect_data();

$userid   = active_userid();
$user     = User::from_userid($userid);
if(!$user) { internal_error("Unrecognized userid: $userid"); }

$status = $redirect_data['status'] ?? null;
if($status) { set_status_message(...$status); }

start_login_page('login');
$nonce = start_login_form("Update User Profile","updateprof");

add_login_input("locked",array(
  'name'  => 'userid',
  'value' => $userid,
  'placeholder' => $userid,
));

add_login_input("password",array(
  'name'  => "password",
  'label' => 'Password',
  'info'  => login_info_html('password'),
  'placeholder' => "password associated with $userid",
));

$fullname = $redirect_data['fullname'] ?? $user->fullname();
add_login_input("fullname",array(
  "label" => 'Name',
  "value" => $fullname,
  "placeholder" => "how your name will appear on the survey",
  "info" => login_info_html("fullname"),
));

$email = $redirect_data['email'] ?? $user->email();
add_login_input("email",array(
  "optional" => True, 
  "value" => $email,
  "placeholder" => "for notifcations and password reset",
  "info" => login_info_html("email"),
));

add_login_submit("Update",'update',true);

close_login_form();
end_page();

die();

