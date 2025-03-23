<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/login.php'));
require_once(app_file('include/redirect.php'));
require_once(app_file('include/sendmail.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/validation.php'));


// Note that all the logic in this file is wrapped up in functions...
//   the very last action in this file is execute the main entry point for 
//   handling post requests from the userid/password recovery form.
//
// As sumch this file should be included using require rather than require_once.

// Except that we will validate the nonce right up front...
validate_post_nonce('recover');

function handle_recover_form()
{
  try {
    if(!key_exists('action',$_POST)) {
      internal_error("Recover handler triggered without action");
    }

    switch( $action = $_POST['action'] ) 
    {
    case 'cancel':
      clear_redirect_data();
      break;
    case 'recover':
      handle_recover_userid_password();
      break;
    default:
      internal_error("Unrecognized recover action: $action");
      break;
    }
  }
  catch (BadInput $e) {
    // Something went wrong processing the recover form
    //   Set the error status
    //   Cache inputs and set redirect to return to this page
    set_error_status($e->getMessage());
    add_redirect_data('userid',   $_POST['userid']   ?? null);
    add_redirect_data('email',    $_POST['email']    ?? null);
    set_redirect_page('recover');
  }
  catch (Exception $e) {
    internal_error($e->getMessage());
  }
}

function handle_recover_userid_password()
{
  $userid    = $_POST['userid'] ?? null;
  $email     = $_POST['email']  ?? null;

  $users = array();

  // if userid is provided, use that for password recovery (ignore email)
  if($userid) {
    $user = User::from_userid($userid);
    if(!$user) {
      log_warning("Invalid userid specified for recovery attempt ($userid)");
      throw new BadInput("No profile found for $userid");
    }
    $email = $user->email();
    if(!$email) {
      log_warning("Recovery requested for $userid, but there is no associated email address");
      throw new BadInput("No email address associatd with $userid");
    }
    $users[] = $user;
  }
  // otherwise if email is provided, try using the email address for password recovery
  elseif($email) {
    $users = User::from_email($email);
    if(!$users) {
      log_warning("Invalid email specified fore recovery attempt ($email)");
      throw new BadInput("No user profile was found for $email");
    }
  }
  // if neither is specified... return status to indicate that one or the other must be specified
  else {
    throw new BadInput("Must specify userid or password");
  }

  // We've found at least one userid to send a recovery email
  //   We want to return to the main login page
  log_info("Sending login recovery to $email");
  foreach($users as $user) {
    log_info("  email sent for ".$user->userid());
  }
  sendmail_recovery($email,$users);
  set_info_status("Login recovery instructions sent to $email");
}

handle_recover_form();

header('Location: '.app_uri());
die();

