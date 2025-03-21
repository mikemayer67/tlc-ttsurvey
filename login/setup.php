<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

// Look for login nonce...
//   If it exists, see if there is additional login info
//
//     action=login      => attemt to login with userid/password
//     action=register   => attempt to login as a new user
//     action=pwrecover  => request a password reset email
//     action=pwreset    => attempt to set a new password


// If the login nonce was not set, it's time to show the standard login form

require_once(app_file('include/page_elements.php'));
require_once(app_file('include/status.php'));
require_once(app_file('include/users.php'));
require_once(app_file('login/elements.php'));

start_page('login');

start_login_form("Survey Login","login");
add_resume_buttons();
add_login_input("userid");
add_login_input("password");

add_login_checkbox("remember", array(
  "label" => "Remember Me",
  "value" => True,
  'info' => "<p>Sets a cookie on your browser so that you need not enter your password on fugure logins</p>",
));


add_login_submit("Log in","login");

add_login_links([
  ['forgot login info', 'recovery', 'left'],
  ['register', 'register', 'right'],
]);

close_login_form();


end_page();
die();


