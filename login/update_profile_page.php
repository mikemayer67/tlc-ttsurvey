<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

// don't drop the nonce as this may be required multiple times from same user menu
validate_get_nonce('user-menu',false);

require_once(app_file('include/elements.php'));
require_once(app_file('login/elements.php'));
require_once(app_file('include/users.php'));

start_login_page('login');

$userid   = active_userid();
$user     = User::from_userid($userid);
$fullname = $user->fullname();
$email    = $user->email();

$nonce = start_login_form("Update User Profile","changeprof");

add_login_input("locked",array(
  'name'  => 'userid',
  'value' => $userid,
  'placeholder' => $userid,
));
add_login_input("password",array(
  'name'  => "password",
  'label' => 'Current Password',
  'info'  => login_info_html('password'),
  'placeholder' => "password associated with $userid",
));

add_login_input("fullname",array(
  "label" => 'Name',
  "value" => $fullname,
  "placeholder" => "how your name will appear on the survey",
  "info" => login_info_html("fullname"),
));

add_login_input("email",array(
  "optional" => True, 
  "value" => $email,
  "placeholder" => "for notifcations and password reset",
  "info" => login_info_html("email"),
));


add_login_submit("Update",'update',true);

close_login_form();
end_page();

die();

