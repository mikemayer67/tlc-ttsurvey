<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/redirect.php'));
require_once(app_file('login/elements.php'));

start_login_page('login');

$redirect_data = get_redirect_data();

$nonce = start_login_form("Survey Login","login");
add_hidden_submit('action','login');

$userid   = $redirect_data['userid']   ?? null;
$remember = $redirect_data['remember'] ?? True;

add_resume_buttons($nonce);
add_login_input("userid", array(
  'value' => $userid,
  'placeholder' => 'userid selected when you registered',
));
add_login_input("password", array(
  'placeholder' => 'password associated with your userid',
));

add_login_checkbox("remember", array(
  "label" => "Add Reconnect Button",
  "value" => $remember,
  'info' => login_info_html('remember'),
));


add_login_submit("Log in","login");

echo "<div class='resume-label'>Or First Time Login:</div>";

$form_uri = app_uri();
echo "<div class='register'><a href='$form_uri?p=register'>Register for the Survey</a></div>";

echo "<div class='recover'><a href='$form_uri?p=recover'>forgot login info</a></div>";

close_login_form();

end_page();
