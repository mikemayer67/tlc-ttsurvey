<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/page_elements.php'));
require_once(app_file('include/status.php'));
require_once(app_file('include/users.php'));
require_once(app_file('login/elements.php'));

start_page('login');

$nonce = start_login_form("Survey Login","login");

add_resume_buttons($nonce);
add_login_input("userid", array('value'=>$_POST['userid']??null) );
add_login_input("password");

add_login_checkbox("remember", array(
  "label" => "Add Resume Button",
  "value" => ($_POST['remember']??1) ? True : False,
  'info' => "<p>Sets a cookie on your browser so that to enable future login without a password</p>",
));


add_login_submit("Log in","login");

add_login_links([
  ['forgot login info', 'pwrecover', 'left'],
  ['register', 'register', 'right'],
]);

close_login_form();

end_page();
