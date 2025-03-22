<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

$get_nonce   = $_GET['ttt'] ?? '';
$post_nonce  = $_POST['nonce'] ?? '';
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

// Handle GET requests for other login pages
$page = $_GET['p'] ?? null;
if($page) {
  $page = app_file("login/$page.php");
  if(!file_exists($page)) { api_die(); }
  require($page);
  die();
}

// Handle POST requests to log in
if($post_nonce) {
  require_once(app_file('login/post.php'));
  handle_post_login($post_nonce,$login_nonce);
}

// All login requests handled, but still not logged in...
//   Present the standard login form

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

die();


