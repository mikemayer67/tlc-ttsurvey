<?php
namespace TLC\TTSurvey;

/**
 * Handle the user login form
 */

if( ! defined('WPINC') ) { die; }

require_once plugin_path('shortcode/login/_elements.php');

add_status_message();

start_login_form("Register for the Survey","register");

if( $_POST['refresh'] ?? False ) {
  $userid = $_POST['userid'] ?? null;
  $fullname = $_POST['fullname'] ?? null;
  $email = $_POST['email'] ?? null;
  $remember = filter_var($_POST['remember']??false, FILTER_VALIDATE_BOOLEAN);
} else {
  $userid = null;
  $fullname = null;
  $email = null;
  $remember = True;
}

add_login_input("userid",array(
  "label" => "Userid",
  "value" => $userid,
  "info" => info_text("userid"),
));

add_login_input("new-password",array(
  "name" => "password",
  "info" => info_text("new-password"),
));

add_login_input("fullname",array(
  "label" => 'Name',
  "value" => $fullname,
  "info" => info_text("fullname"),
));

add_login_input("email",array(
  "optional" => True, 
  "value" => $email,
  "info" => info_text("email"),
));

# default to true on blank form
# otherwise set to true if currently checked
add_login_checkbox("remember", array(
  "label" => "Remember Me",
  "value" => $remember,
  'info' => "<p>Sets a cookie on your browser so that you need not enter your password on fugure logins</p>",
));

add_login_submit("Register",'register',true);

close_login_form();

