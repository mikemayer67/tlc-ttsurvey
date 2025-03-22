<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

$get_nonce   = $_GET['ttt'] ?? '';
$login_nonce = $_SESSION['nonce']['login'] ?? 'NO_NONCE';
$_SESSION['nonce']['login'] = null;

// Look for login nonce...
//   If it exists, see if there is additional login info
//
//     action=login      => attemt to login with userid/password
//     action=register   => attempt to login as a new user
//     action=pwrecover  => request a password reset email
//     action=pwreset    => attempt to set a new password


// Handle request to forget an access token
$forget = $_GET['forget'] ?? null;
if($forget) {
  if($get_nonce !== $login_nonce) {
    log_warning("Login request made without proper nonce: ".print_r($_SERVER['REQUEST_URI'],true));
    api_die();
  }
  forget_user_token($forget);
  header('Location: '.app_uri());
  die();
}

// Handle requests for other login pages
log_dev("GET = ".print_r($_GET,true));
$page = $_GET['p'] ?? null;
if($page) {
  $page = app_file("login/$page.php");
  if(!file_exists($page)) { api_die(); }
  require($page);
  die();
}

require_once(app_file('include/page_elements.php'));
require_once(app_file('include/status.php'));
require_once(app_file('include/users.php'));
require_once(app_file('login/elements.php'));

start_page('login');

$nonce = start_login_form("Survey Login","login");
add_resume_buttons($nonce);
add_login_input("userid");
add_login_input("password");

add_login_checkbox("remember", array(
  "label" => "Remember Me",
  "value" => True,
  'info' => "<p>Sets a cookie on your browser so that you need not enter your password on fugure logins</p>",
));


add_login_submit("Log in","login");

add_login_links([
  ['forgot login info', 'pwrecover', 'left'],
  ['register', 'register', 'right'],
]);

close_login_form();


end_page();
die();


