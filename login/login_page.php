<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/page_elements.php'));
require_once(app_file('include/redirect.php'));
require_once(app_file('login/elements.php'));

start_page('login');

$redirect_data = get_redirect_data();

$nonce = start_login_form("Survey Login","login");
add_hidden_submit('action','login');

$userid   = $redirect_data['userid']   ?? null;
$remember = $redirect_data['remember'] ?? True;

add_resume_buttons($nonce);
add_login_input("userid", array('value' => $userid) );
add_login_input("password");

add_login_checkbox("remember", array(
  "label" => "Add Reconnect Button",
  "value" => $remember,
  'info' => info_text('remember'),
));


add_login_submit("Log in","login");

add_login_links([
  ['forgot login info', 'recover', 'left'],
  ['register', 'register', 'right'],
]);

close_login_form();

end_page();
