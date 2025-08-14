<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/validation.php'));
require_once(app_file('include/login.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/roles.php'));

validate_ajax_nonce('admin-login');

log_info("Logging in Admin");

$userid   = adjust_user_input('userid',   $_POST['userid']   ?? '');
$password = adjust_user_input('password', $_POST['password'] ?? '');

$config = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true);
$admin_username = $config['admin_username'] ?? null;
$admin_password = $config['admin_password'] ?? null;

if( ($userid===$admin_username) && ($password === $admin_password) ) 
{
  log_info("Admin login as $userid");
  $_SESSION['admin-id'] = $userid;
  $rval = ['success'=>true];
}
else if(validate_user_password($userid,$password)) 
{
  $roles = user_roles($userid);
  if( count($roles) > 0 ) {
    logout_active_user();
    $user = User::from_userid($userid);
    start_survey_as($user);
    
    $rval = ['success'=>true];
  } else {
    $rval = ['success'=>false, 'error'=>"$userid has no admin roles"];
  }
}
else
{
  $rval = ['success'=>false, 'error'=>'Invalid userid/password'];
}

echo json_encode($rval);
die();
