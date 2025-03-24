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
//   handling post requests from the pwreset form.
//
// As sumch this file should be included using require rather than require_once.

// Except that we will validate the nonce right up front...
validate_post_nonce('pwreset');

function handle_pwreset_form()
{
  try{
    if(!key_exists('action',$_POST)) {
      internal_error("Password reset handler triggered without action");
    }

    switch( $action = $_POST['action'] )
    {
    case 'cancel':
      clear_redirect_data();
      break;
    case 'pwreset':
      handle_password_reset();
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
    set_redirect_page('pwreset');
  }
  catch (Exception $e) {
    internal_error($e->getMessage());
  }
}

function handle_password_reset()
{
  $userid    = $_POST['userid']           ?? null;
  $token     = $_POST['token']            ?? null;
  $password  = $_POST['password']         ?? null;
  $pwconfirm = $_POST['password-confirm'] ?? null;

  if(!$userid)    { internal_error("Missing userid in register request"); }
  if(!$token)     { internal_error("Missing token in register request"); }
  if(!$password)  { internal_error("Missing password in register request"); }
  if(!$pwconfirm) { internal_error("Missing password-confirm in register request"); }

  $userid   = adjust_user_input('userid',   $userid);
  $token    = adjust_user_input('token',    $token);
  $password = adjust_user_input('password', $password);

  $user = User::from_userid($userid);
  if(!$user) {
    throw new BadInput("Unrecognized userid ($userid)");
  }

  $error = '';
  if(!adjust_and_validate_user_input('password',$password,$error)) {
    throw new BadInput("Invalid password ($error)");
  }
  if($password !== $pwconfirm) {
    throw new BadInput("Passwords do not match");
  }

  if(!$user->update_password($token,$password,$error)) {
    throw new BadInput($error);
  }
  
  set_info_status(
    "The password for $userid has been reset." .
    " You should now be able to log in with your new password.");
}


handle_pwreset_form();

clear_redirect_data();
header("Location: ".app_uri());
die();
