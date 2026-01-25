<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/validation.php'));
require_once(app_file('include/login.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-login');

start_ob_logging();

log_info("Logging in Admin");

$userid   = parse_ajax_string_input('userid');
$password = parse_ajax_string_input('password');

$userid   = adjust_user_input('userid',   $userid);
$password = adjust_user_input('password', $password);

$config = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true);
$admin_username = $config['admin_username'] ?? null;
$admin_password = $config['admin_password'] ?? null;

$response = new AjaxResponse();

if( ($userid===$admin_username) && ($password === $admin_password) ) 
{
  log_info("Admin login as $userid");
  $_SESSION['admin-id'] = $userid;
}
else if(validate_user_password($userid,$password)) 
{
  $roles = user_roles($userid);
  if($roles) {
    logout_active_user();
    $user = User::from_userid($userid);
    start_survey_as($user);
  } else {
    $response->fail("$userid has no admin roles");
  }
}
else
{
  $response->fail('Invalid userid/password');
}

end_ob_logging();

$response->send();
die();
