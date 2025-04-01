<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/login.php'));
require_once(app_file('include/redirect.php'));
require_once(app_file('include/status.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/validation.php'));

// Note that all the logic in this file is wrapped up in functions...
//   the very last action in this file is execute the main entry point for 
//   handling post requests from the register form.
//
// As sumch this file should be included using require rather than require_once.

function handle_register_form()
{
  validate_and_drop_nonce('register');

  try {
    if(!key_exists('action',$_POST)) {
      internal_error("Register handler triggered without action");
    }

    switch( $action = $_POST['action'] )
    {
    case 'cancel':
      clear_redirect_data();
      break;
    case 'register':
      handle_register_new_user();
      break;
    default:
      internal_error("Unrecognized register action: $action");
      break;
    }
  }
  catch (BadInput $e) {
    // Something went wrong processing the register form
    //   Set the error status
    //   Cache inputs and set redirect to return to this page
    set_error_status($e->getMessage());
    add_redirect_data('userid',   $_POST['userid']   ?? null);
    add_redirect_data('fullname', $_POST['fullname'] ?? null);
    add_redirect_data('email',    $_POST['email']    ?? null);
    add_redirect_data('remember', $_POST['remember'] ?? null);
    set_redirect_page('register');
  }
  catch (Exception $e) {
    internal_error($e->getMessage());
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


  $error = '';
  $pwconfirm = adjust_user_input('password',$pwconfirm);

  if(!adjust_and_validate_user_input('userid',$userid,$error)) {
    throw new BadInput("Invalid userid ($error)");
  }
  if(!adjust_and_validate_user_input('password',$password,$error)) {
    throw new BadInput("Invalid password ($error)");
  }
  if($password !== $pwconfirm) {
    throw new BadInput("Passwords do not match");
  }
  if(!adjust_and_validate_user_input('fullname',$fullname,$error)) {
    throw new BadInput("Invalid name ($error)");
  }
  if(!adjust_and_validate_user_input('email',$email,$error)) {
    throw new BadInput("Invalid email ($error)");
  }

  // all our inputs look good, add the new user to the database
  //   and get the new User instance

  $user = create_new_user($userid,$fullname,$password,$email); 

  // Just in case we failed to create a new user
  if(!$user) {
    $token = gen_token(6);
    log_error("[$token] Failed to create user ($userid, $fullname, password, $email)");
    throw BadInput("Failed to create user [error #$token]");
  }

  // user created
  //   set this as the active user
  //   add userid/token to cached tokens if requested
  start_survey_as($user);

  if( $remember ) { remember_user_token($userid,$user->access_token()); }
}

handle_register_form();

header("Location: ".app_uri());
die();
