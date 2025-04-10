<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('/include/redirect.php'));
require_once(app_file('/include/users.php'));
require_once(app_file('/include/roles.php'));
require_once(app_file('/include/status.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/validation.php'));

function handle_admin_form()
{
  validate_nonce('admin');

  unset($_SESSION['admin-id']);

  $action   = $_POST['action']   ?? null;
  $userid   = $_POST['userid']   ?? null;
  $password = $_POST['password'] ?? null;

  if(!$action)   { internal_error("Missing action in admin login request"); }
  if(!$userid)   { internal_error("Missing userid in admin login request"); }
  if(!$password) { internal_error("Missing password in admin login request"); }

  if($action !== 'admin') { intereral_error("Invalid action ($action) in admin login request"); }

  $userid   = adjust_user_input('userid',$userid);
  $password = adjust_user_input('password',$password);

  log_dev("attempt admin login: $userid/$password");

  $config = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true);
  $admin_username = $config['admin_username'] ?? null;
  $admin_password = $config['admin_password'] ?? null;
  if( ($userid === $admin_username) && ($password === $admin_password) ) {
    clear_status();
    clear_redirect_data();
    log_info("Admin login as $userid");

    $_SESSION['admin-id'] = $userid;
  } 
  else {
    log_warning("Invalid admin login attempt: bad admin_username ($userid)");
    set_error_status("invalid userid or password");
    add_redirect_data('userid',$userid);
  }

  log_dev(print_r($_SESSION,true));

  header('Location: '.app_uri().'?admin');
  die();
}

handle_admin_form();
