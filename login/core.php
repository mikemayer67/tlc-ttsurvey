<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/redirect.php'));

// Handle special requests that use GET queries

if(key_exists('forget',$_GET)) 
{
  // nonce from get request, but don't drop it as this isn't an actual
  //   form submission
  validate_nonce('login',false);

  $forget = $_GET['forget'] ?? null;
  forget_user_token($forget);
  header('Location: '.app_uri());
  die();
}

// In addition to the main login page, there are a handful of supplemental login pages.  
// These can be specified via:
//   - the URI query parameter 'p'  (e.g. http://mysite.com/tt.php?p=recover)
//   - the session data (e.g. $_SESSION['redirect-page'] = 'recover')
// These SHOULD be mutually exclusive, but if there is conflict, the session data takes precedence

$page = get_redirect_page();
$page = $page ? $page : $_GET['p'] ?? null;

// If a specifc redirect page was specified, jump to that now

if($page)
{
  $page = app_file("login/{$page}_page.php");
  if(!file_exists($page)) { internal_error("Unimplemented redirect page encountered ($page)"); }
  require($page);
  die();
}

// Handle POST requests

if( $form=$_POST['form']??null ) {
  $handler = app_file("login/{$form}_handler.php");
  if(!file_exists($handler)) {
    internal_error("Unimplemented form handler ($form / $handler)");
  }
  require($handler);
}

// If we got here, load the main login page

require(app_file('login/login_page.php'));
die();


