<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

// Note that all the logic in this file is wrapped up in functions...
//   the very last action in this file is execute the main entry point for 
//   handling post requests from the register form.
//
// As sumch this file should be included using require rather than require_once.

// Except that we will validate the nonce right up front...
validate_post_nonce('register');

function handle_register_form()
{
  // all register requests come through the post action parameter
  if( $action = $_POST['action'] ?? null ) {
    switch($action) {
      case 'cancel':
        // nothing to do on cancel... simply return to continue loading the main login page
        break;
      case 'register':
        handle_register_new_user();
        break;
      default:
        internal_error("Unrecognized register action: $action");
        break;
    }
  }
}

function handle_register_new_user()
{
  $userid    = $_POST['userid']           ?? null;
  $password  = $_POST['password']         ?? null;
  $pwconfirm = $_POST['password-confirm'] ?? null;
  $fullname  = $_POST['fullname']         ?? null;
  $email     = $_POST['email']            ?? null;
  $remember  = $_POST['remember']         ?? 0;

  if(!$userid)    { internal_error("Missing userid in register request"); }
  if(!$password)  { internal_error("Missing password in register request"); }
  if(!$pwconfirm) { internal_error("Missing password-confirm in register request"); }
  if(!$fullname)  { internal_error("Missing fullname in register request"); }

  require_once(app_file('include/validation.php'));
  require_once(app_file('include/users.php'));
  require_once(app_file('include/login.php'));

  // if we encounter an error, we're going to want to go back to the register page,
  //   not the main login page. 
  $_GET['p'] = "register";

  $error = '';
  if(!adjust_and_validate_user_input('userid',$userid,$error)) {
    set_error_status("Invalid userid ($error)");
    return;
  }
  elseif(!adjust_and_validate_user_input('password',$password,$error)) {
    set_error_status("Invalid password ($error)");
    return;
  }
  elseif($password !== $pwconfirm) {
    set_error_status("Passwords do not match");
    return;
  }
  elseif(!adjust_and_validate_user_input('fullname',$fullname,$error)) {
    set_error_status("Invalid name ($error)");
    return;
  }
  elseif(!adjust_and_validate_user_input('email',$email,$error)) {
    set_error_status("Invalid email ($error)");
    return;
  }

  // all our inputs look good, add the new user to the database
  //   and get the new User instance

  $user = create_new_user($userid,$fullname,$password,$email); 

  if(!$user) {
    // something went wrong... not sure what... log it and return failure
    $token = gen_token(6);
    log_error("[$token] Failed to create user ($userid, $fullname, password, $email)");
    set_error_status("Failed to create user [error #$token]");
    return;
  }

  // succesfully created new user, 
  //   set this as the active user
  //   add userid/token to cached tokens if requested
  //   redirect to survey entry point to reload with the survey

  start_survey_as($user);

  if( $remember ) {
    remember_user_token($userid,$user->access_token());
  }

  header("Location: ".app_uri());
  die();
}


handle_register_form();
