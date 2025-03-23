<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

// Note that all the logic in this file is wrapped up in functions...
//   the very last action in this file is execute the main entry point for 
//   handling post requests from the userid/password recovery form.
//
// As sumch this file should be included using require rather than require_once.

// Except that we will validate the nonce right up front...
validate_post_nonce('recover');

function handle_recover_form()
{
  // all recover requests come through the post action parameter
  if( $action = $_POST['action'] ?? null ) {
    switch($action) {
      case 'cancel':
        // clear the POST fields and return to continue loading the main login page
        $_POST = array();
        break;
      case 'recover':
        handle_recover_userid_password();
        break;
      default:
        internal_error("Unrecognized recover action: $action");
        break;
    }
  }
}

function handle_recover_userid_password()
{
  $userid    = $_POST['userid'] ?? null;
  $email     = $_POST['email']  ?? null;

  require_once(app_file('include/validation.php'));
  require_once(app_file('include/users.php'));
  require_once(app_file('include/login.php'));
  require_once(app_file('include/sendmail.php'));

  // if we encounter an error, we're going to want to go back to the register page,
  //   not the main login page. 
  $_GET['p'] = "recover";

  $error = '';
  // if userid is provided, use that for password recovery (ignore email)
  if($userid) {
    $user = User::from_userid($userid);
    if(!$user) {
      log_warning("Invalid userid specified for recovery attempt ($userid)");
      set_error_status("No profile found for $userid");
      return;
    }
    $email = $user->email();
    if(!$email) {
      log_warning("Recovery requested for $userid, but there is no associated email address");
      set_error_status("No email address associatd with $userid");
      return;
    }
    $users = array($user);
  }
  elseif($email) {
    $users = User::from_email($email);
    if(!$users) {
      log_warning("Invalid email specified fore recovery attempt ($email)");
      set_error_status("No profile was found with the specified email address");
      return;
    }
  }
  else {
    set_error_status("Must specify userid or password");
    return;
  }

  // We've found at least one userid to send a recovery email
  //   We want to return to the main login page
  log_info("Sending login recovery to $email");
  foreach($users as $user) {
    log_info("  email sent for ".$user->userid());
  }
  sendmail_recovery($email,$users);

  $_GET['p'] = "login";
  set_info_status("Login recovery instructions sent to $email");
}

handle_recover_form();

