<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

function handle_post_login($post_nonce,$login_nonce)
{
  // verify that the nonces match
  if($post_nonce !== $login_nonce) {
    log_warning("Recover request made without proper nonce ($post_nonce/$login_nonce)");
    api_die();
  }

  // dispatch based on specific login request
  if( key_exists('resume',$_POST) ) { 
    handle_token_login(); 
  }
  elseif( key_exists('userid',$_POST) && key_exists('password',$_POST) ) {
    handle_password_login(); 
  }
  else {
    internal_error("Unrecognized login POST request received: ".print_r($_POST,true));
  }
}

function handle_token_login()
{
  require_once(app_file('/include/login.php'));
  require_once(app_file('/include/status.php'));

  $resume = $_POST['resume'] ?? null;
  if(!$resume) { internal_error("Invalid resume request: $resume"); }
  list($userid,$token) = explode(':',$resume);

  if( !resume_survey_as($userid,$token) ) {
    // failed to log in 
    //   forget the bad token, set status, and continue loading login page
    forget_user_token($userid);
    set_error_status("Accsss token for $userid is no longer valid");
  }

  // redirect to the survey entry point to reload with the survey
  header("Location: ".app_uri());
  die();
}

function handle_password_login()
{
  log_dev("COOKIE: ".print_r($_COOKIE,true));
  require_once(app_file('/include/users.php'));
  require_once(app_file('/include/login.php'));
  require_once(app_file('/include/status.php'));

  $userid   = $_POST['userid']   ?? '';
  $password = $_POST['password'] ?? '';
  $remember = $_POST['remember'] ?? 0;

  $user = User::from_userid($userid);
  if(!$user) {
    // invalid userid
    //   set status and continue loading login page
    set_error_status("Invalid userid");
    return;
  }

  if(! $user->validate_password($password) ) {
    // invalid password
    //   set status and continue loading login page
    set_error_status("Invalid password");
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
