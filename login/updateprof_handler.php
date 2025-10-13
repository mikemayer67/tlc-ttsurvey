<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/users.php'));
require_once(app_file('include/redirect.php'));

// Note that all the logic in this file is wrapped up in functions...
//   the very last action in this file is execute the main entry point for 
//   handling post requests from the pwreset form.
//
// As sumch this file should be included using require rather than require_once.

function handle_updateprof_form()
{
  validate_and_retain_nonce('updateprof');

  $action = $_POST['action'] ?? null;
  if(!$action) { internal_error("Missing action in update request");   }

  if($action === 'cancel') { 
    add_redirect_data('message',"Update Profile Cancelled");
    set_redirect_page('close');
    return;
  }

  if($action !== 'update') {
    internal_error("Invalid action ($action) in update request");
  }

  $userid   = adjust_user_input('userid',  $_POST['userid']   ?? null);
  $passwd   = adjust_user_input('password',$_POST['password'] ?? null);
  $fullname = adjust_user_input('fullname',$_POST['fullname'] ?? null);
  $email    = adjust_user_input('email',   $_POST['email']    ?? null);

  if(!$userid) { internal_error("Missing userid in update request");   }
  if(!$passwd) { internal_error("Missing password in update request"); }

  $user = User::from_userid($userid);
  if(!$user) { 
    handle_error("Unrecognized userid: $userid"); 
    return;
  }

  if(!validate_user_password($userid,$passwd)) {
    handle_error("Incorrect password for $userid");
    return;
  }

  $old_fullname = $user->fullname();
  $old_email    = $user->email();

  if( !($fullname || $email) ) {
    handle_error("Nothing has changed from current profile");
    return;
  }

  if($fullname !== $old_fullname) {
    // assume if it matches the current value, it's ok
    $error = '';
    if(!validate_user_input('fullname',$fullname,$error)) {
      handle_error("$fullname is not a valid name: $error");
      return;
    }
  }

  if($email && ($email !== $old_email)) {
    // assume if it matches the current value, it's ok
    $error = '';
    if(!validate_user_input('email',$email,$error)) {
      handle_error("$email is not a valid email: $error");
      return;
    }
  }

  if(!$user->update_profile_and_notify($fullname,$email)) {
    handle_error("Failed to update profile");
    return;
  }

  // success
  add_redirect_data('message',"User Profile Updated");
  add_redirect_data('email',$user->email() ?? '');
  set_redirect_page('close');
}

function handle_error($msg)
{
  add_redirect_data('status',[$msg,'error']);
  set_redirect_page('updateprof');
  $nonce = get_nonce('update-page');
  $app_uri = app_uri("ttt=$nonce");
  header("Location: $app_uri");
  die();
}

handle_updateprof_form();

$app_uri = app_uri();
header("Location: $app_uri");
die();
 


