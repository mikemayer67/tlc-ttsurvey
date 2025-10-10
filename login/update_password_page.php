<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

// don't drop the nonce as this may be required multiple times from same user menu
validate_get_nonce('user-menu',false);

require_once(app_file('include/elements.php'));
require_once(app_file('login/elements.php'));
require_once(app_file('include/users.php'));

start_login_page('login');

$userid = active_userid();
$user = User::from_userid($userid);
$username = $user->fullname();

$nonce = start_login_form("Change Password","changepw");

add_login_input("locked",array(
  'name'  => 'userid',
  'value' => $userid,
  'placeholder' => "$userid ($username)",
));
add_login_input("password",array(
  'name'  => "password",
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

