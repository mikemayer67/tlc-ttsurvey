<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('/include/login.php'));
require_once(app_file('/include/redirect.php'));
require_once(app_file('/include/status.php'));
require_once(app_file('include/logger.php'));


// Note that all the logic in this file is wrapped up in functions...
//   the very last action in this file is execute the main entry point for 
//   handling post requests from the login form.
//
// As sumch this file should be included using require rather than require_once.

function handle_login_form()
{
  validate_nonce('login');

  try {
    // handle resume buttons
    if( $resume = $_POST['resume'] ?? null ) 
    {
      handle_login_with_token($resume);
    }
    elseif( $action = $_POST['action'] ?? null )
    {
      switch($action) {
      case 'login':
        handle_login_with_password();
        break;
      default:
        internal_error("Unrecognized login action: $action");
      }
    }
    else 
    {
      internal_error("Login handler triggered without resume or login action"); 
    }
    
  }
  catch (BadInput $e) {
    // Something went wrong processing the login form
    //   Set the error status
    //   Cache the userid and remember inputs
    //   Set the redirect page to the main login entry page
    set_error_status($e->getMessage());
    add_redirect_data('userid',  $_POST['userid']   ?? null);
    add_redirect_data('remember',$_POST['remember'] ?? null);
    set_redirect_page('login');
  }
  catch (Exception $e) {
    internal_error($e->getMessage());
  }

  // Redirect back to the main survey entry point
  header('Location: '.app_uri());
  die();
}

function handle_login_with_token($resume)
{
  // extract the userid and token from the resume input
  list($userid,$token) = explode(':',$resume);

  // try to resume with provided userid/token
  //   if not, raise an exception
  if( !resume_survey_as($userid,$token) )
  {
    forget_user_token($userid);
    throw new BadInput("Access token for $userid is no longer valid");
  } 
}

function handle_login_with_password()
{
  $userid   = $_POST['userid']   ?? null;
  $password = $_POST['password'] ?? null;

  if(!$userid)   { internal_error("Missing userid in login request"); }
  if(!$password) { internal_error("Missing password in login request"); }

  // validate input userid and passoword

  $user = User::from_userid($userid);

  if(!$user) { 
    throw new BadInput("Invalid userid ($userid)"); 
  }

  if(!$user->validate_password($password)) {
    throw new BadInput("Invalid password for $userid");
  }

  // success
  //   set the active user
  //   add userid/token to cached access tokens if requested
  //   redirect to survey entry point to reload with the survey

  start_survey_as($user);

  $remember = $_POST['remember'] ?? 0;
  if( $remember ) {
    remember_user_token($userid,$user->access_token());
  }
}

handle_login_form();
