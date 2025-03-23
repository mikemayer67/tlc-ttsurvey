<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/page_elements.php'));
require_once(app_file('include/status.php'));
require_once(app_file('include/users.php'));
require_once(app_file('login/elements.php'));

start_page('login');

$nonce = start_login_form("Survey Login","login");

if( $_POST['refresh'] ?? False ) {
  $userid = $_POST['userid'] ?? null;
  $remember = filter_var($_POST['remember'] ?? False, FILTER_VALIDATE_BOOLEAN);
} else {
  $userid = null;
  $remember = True;
}

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
  ['forgot login info', 'pwrecover', 'left'],
  ['register', 'register', 'right'],
]);

close_login_form();

end_page();
