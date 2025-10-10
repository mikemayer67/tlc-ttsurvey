<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));
require_once(app_file('login/elements.php'));

start_login_page('login');

$redirect_data = get_redirect_data();

$nonce = start_login_form("Register for the Survey","register");

$userid   = $redirect_data['userid']   ?? null;
$fullname = $redirect_data['fullname'] ?? null;
$email    = $redirect_data['email']    ?? null;
$remember = $redirect_data['remember'] ?? True;

add_login_input("userid",array(
  "label" => "Userid",
  "value" => $userid,
  "placeholder" => "enter your desired survey login name",
  "info" => login_info_html("userid"),
));

add_login_input("new-password",array(
  "name" => "password",
  "info" => login_info_html("new-password"),
));

add_login_input("fullname",array(
  "label" => 'Name',
  "value" => $fullname,
  "placeholder" => "how your name will appear on the survey",
  "info" => login_info_html("fullname"),
));

add_login_input("email",array(
  "optional" => True, 
  "value" => $email,
  "placeholder" => "for notifcations and password reset",
  "info" => login_info_html("email"),
));

# default to true on blank form
# otherwise set to true if currently checked
add_login_checkbox("remember", array(
  "label" => "Add Reconnect Button",
  "value" => $remember,
  'info' => login_info_html("remember"),
));

add_login_submit("Register",'register',true);

close_login_form();
end_page();

die();
