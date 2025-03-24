<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/page_elements.php'));
require_once(app_file('login/elements.php'));

start_page('login');

$redirect_data = get_redirect_data();

$nonce = start_login_form("Register for the Survey","register");

$userid   = $redirect_data['userid']   ?? null;
$fullname = $redirect_data['fullname'] ?? null;
$email    = $redirect_data['email']    ?? null;
$remember = $redirect_data['remember'] ?? True;

add_login_input("userid",array(
  "label" => "Userid",
  "value" => $userid,
  "info" => info_text("userid"),
));

add_login_input("new-password",array(
  "name" => "password",
  "info" => info_text("new-password"),
));

add_login_input("fullname",array(
  "label" => 'Name',
  "value" => $fullname,
  "info" => info_text("fullname"),
));

add_login_input("email",array(
  "optional" => True, 
  "value" => $email,
  "info" => info_text("email"),
));

# default to true on blank form
# otherwise set to true if currently checked
add_login_checkbox("remember", array(
  "label" => "Add Reconnect Button",
  "value" => $remember,
  'info' => info_text("remember"),
));

add_login_submit("Register",'register',true);

close_login_form();
end_page();

die();
