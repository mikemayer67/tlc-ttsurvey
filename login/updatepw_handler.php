<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/users.php'));
require_once(app_file('include/redirect.php'));

clear_redirect();

// Note that all the logic in this file is wrapped up in functions...
//   the very last action in this file is execute the main entry point for 
//   handling post requests from the pwreset form.
//
// As sumch this file should be included using require rather than require_once.

function handle_updatepw_form()
{
  validate_and_retain_nonce('updatepw');

  $action = $_POST['action'] ?? null;
  if(!$action) { internal_error("Missing action in update request");   }

  if($action === 'cancel') { 
    start_redirect_to_login_page('close')
      ->add('message',"Password Reset Cancelled")
    ;
    return;
  }

  if($action !== 'update') {
    internal_error("Invalid action ($action) in update request");
  }

  $userid = adjust_user_input('userid',  $_POST['userid'] ?? null);
  $cur_pw = adjust_user_input('password',$_POST['password'] ?? null);
  $new_pw = adjust_user_input('password',$_POST['new-password'] ?? null);
  $cnf_pw = adjust_user_input('password',$_POST['new-password-confirm'] ?? null);

  if(!$userid) { internal_error("Missing userid in update request");   }
  if(!$cur_pw) { internal_error("Missing password in update request"); }
  if(!$new_pw) { internal_error("Missing new password in update request"); }
  if(!$cnf_pw) { internal_error("Missing retyped password in update request"); }

  $user = User::from_userid($userid);
  if(!$user) { 
    handle_error("Unrecognized userid: $userid"); 
    return;
  }

  if(!validate_user_password($userid,$cur_pw)) {
    handle_error("Incorrect password for $userid");
    return;
  }

  if($new_pw!==$cnf_pw) {
    handle_error("New/retyped passwords must match");
    return;
  }
  if($new_pw===$cur_pw) {
    handle_error("New password cannot be same as current password");
    return;
  }
  $error = '';
  if(!validate_user_input('password',$new_pw,$error)) {
    handle_error("Invalid new password: $error");
    return;
  }

  if(!$user->set_password_and_notify($new_pw)) {
    handle_error("Failed to update password");
    return;
  }

  // success
  start_redirect_to_login_page('close')
    ->add('message',"User Password Updated")
    ->add('email',$user->email() ?? '')
  ;

  // update session and user access tokens
  require_once(app_file('include/login.php'));
  regen_active_token();
}

function handle_error($msg)
{
  start_redirect_to_login_page('updatepw')
    ->add('status',[$msg,'error'])
  ;
  $nonce = get_nonce('update-page');
  $app_uri = app_uri("ttt=$nonce");
  header("Location: $app_uri");
  die();
}

handle_updatepw_form();

$app_uri = app_uri();
header("Location: $app_uri");
die();
 

