<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

// don't drop the nonce as this may be required multiple times from same user menu
validate_and_retain_get_nonce('update-page');

require_once(app_file('include/elements.php'));
require_once(app_file('login/elements.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/redirect.php'));

$redirect_data = get_redirect_data();

$userid = active_userid();
$user = User::from_userid($userid);
if(!$user) { internal_error("Unrecognized userid: $userid"); }

$status = $redirect_data['status'] ?? null;
if($status) { set_status_message(...$status); }

start_login_page('login');
$nonce = start_login_form("Change Password","changepw");

$username = $user->fullname();
add_login_input("locked",array(
  'name'  => 'userid',
  'value' => $userid,
  'placeholder' => "$userid ($username)",
));

add_login_input("password",array(
  'name'  => "password",
  'value' => $cur_pw,
  'label' => 'Current Password',
  'info'  => login_info_html('password'),
  'placeholder' => "password associated with $userid",
));

add_login_input("new-password",array(
  'name'  => 'new-password',
  'label' => 'New Password',
  'info'  => login_info_html('new-password'),
));

add_login_submit("Update",'update',true);

close_login_form();
end_page();

die();

