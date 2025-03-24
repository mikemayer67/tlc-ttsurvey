<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

//////////////////////////////////////////////////
// Handlers for the main login form
//////////////////////////////////////////////////

function handle_login_form()
{
  validate_post_nonce('login');

  require_once(app_file('/include/login.php'));
  require_once(app_file('/include/status.php'));

  // handle resume button
  if( $resume = $_POST['resume'] ?? null ) {
    list($userid,$token) = explode(':',$resume);
    handle_login_with_token($userid,$token);
  }

  // handle action
  //   for this form, the only recognized action is 'login'
  //   but we'll pretend their could be more for consistency with other forms
  elseif( $action = $_POST['action'] ?? null ) {
    switch($action) {
    case 'login':
      handle_login_with_password();
      break;
    default:
      internal_error("Unrecognized login action: $action");
      break;
    }
  }
}

function handdle_login_with_token($userid,$token)
{
  if( !resume_survey_as($userid,$token) ) {
    // failed to log in
    //   forget bad token, set status, and return to continue loading login page
    forget_user_token($userid);
    set_error_status("Access token for $userid is no longer valid");
    return;
  } 

  // successfully logged in using userid and token
  // redirect to the survey entry point to reload with the survey
  header('Location: '.app_uri());
  die();
}

function handle_login_with_password()
{
  $userid   = $_POST['userid']   ?? null;
  $password = $_POST['password'] ?? null;
  $remember = $_POST['remember'] ?? 0;

  if(!$userid)   { internal_error("Missing userid in login request"); }
  if(!$password) { internal_error("Missing password in login request"); }

  $user = User::from_userid($userid);
  if(!$user) {
    // invalid userid
    //   set status and return to continue loading login page
    set_error_status("Invalid userid ($userid)");
    return;
  }

  if(!$user->validate_password($password)) {
    // invalid password
    //   set status and return to continue loading login page
    set_error_status("Invalid password for $userid");
    return;
  }

  // success
  //   set the active user
  //   add userid/token to cached tokens if requested
  //   redirect to survey entry point to reload with the survey
  start_survey_as($user);

  if( $remember ) {
    remember_user_token($userid,$user->access_token());
  }

  header("Location: ".app_uri());
  die();
}

//////////////////////////////////////////////////
// Handlers for the register new user form
//////////////////////////////////////////////////

function handle_register_form()
{
  validate_post_nonce('login');
  print("<h1>REGISTER</h1>");
  die();
}
