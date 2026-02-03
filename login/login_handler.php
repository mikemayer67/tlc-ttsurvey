<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('/include/cookiejar.php'));
require_once(app_file('/include/redirect.php'));
require_once(app_file('/include/status.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/login.php'));

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
    start_redirect_to_login_page('login')
      ->add('userid',   $_POST['userid']   ?? null )
      ->add('remember', $_POST['remember'] ?? null )
    ;
  }
  catch (\Exception $e) {
    internal_error($e->getMessage());
  }

  // Redirect back to the main survey entry point
  header('Location: '.app_uri());
  die();
}

/**
 * Attempts to log in with a given 'userid:token'
 * @param string $userid_token (colon delineated "userid:token")
 * @return void
 * @throws BadInput if invalid userid:token was provided
 */
function handle_login_with_token(string $userid_token)
{
  // extract the userid and token from the resume input
  list($userid,$token) = explode(':',$userid_token);
  $userid = strtolower($userid);

  // try to resume with provided userid/token
  //   if not, raise an exception
  if(!resume_survey_as($userid,$token)) {
    require_once(app_file('include/cookiejar.php'));
    CookieJar::forget_access_token($userid);
    throw new BadInput("Access token for $userid is no longer valid");
  } 
}

function handle_login_with_password()
{
  $userid   = strtolower($_POST['userid'] ?? '');
  $password = $_POST['password'] ?? '';

  if($userid==='')   { internal_error("Missing userid in login request"); }
  if($password==='') { internal_error("Missing password in login request"); }

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

  $remember = $_POST['remember'] ?? false;
  start_survey_as($userid, $remember);
}

handle_login_form();
