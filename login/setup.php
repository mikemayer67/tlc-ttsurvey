<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/redirect.php'));

// Handle request to forget an access token
//
// This is a special case that doesn't fit well into the GET/POST
//   requests handled below... so we'll just handle it now

if(key_exists('forget',$_GET)) {
  validate_get_nonce('login');
  $forget     = $_GET['forget'] ?? null;
  forget_user_token($forget);
  header('Location: '.app_uri());
  die();
}

// In addition to the main login page, there are a handful of supplemental login pages.  
// These can be specified via:
//   - the URI query parameter 'p'  (e.g. http://mysite.com/tt.php?p=recover)
//   - the session data (e.g. $_SESSION['redirect-page'] = 'recover')
// These SHOULD be mutually exclusive, but if there is conflict, the session data takes precedence

log_dev("SESSION = ".print_r($_SESSION,true));
log_dev("GET = ".print_r($_GET,true));

$page = get_redirect_page();
print("page=$page");
$page = $page ? $page : $_GET['p'] ?? null;
print("page=$page");

// If a specifc redirect page was specified, jump to that now

if($page)
{
  log_dev("redirect to $page");
  log_dev("SESSION = ".print_r($_SESSION,true));

  $page = app_file("login/{$page}_page.php");
  if(!file_exists($page)) { internal_error("Unimplemented redirect page encountered ($page)"); }
  require($page);
  die();
}

// Handle POST requests

log_dev("POST = ".print_r($_POST,true));
if( $form=$_POST['form']??null ) {
  $handler = "login/{$form}_handler.php";
  $handler = app_file($handler);
  if(!file_exists($handler)) {
    internal_error("Unimplemented form handler ($form / $handler)");
  }
  require($handler);
}

// If we got here, load the main login page

require(app_file('login/login_page.php'));
die();


