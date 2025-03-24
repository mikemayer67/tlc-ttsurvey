<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/page_elements.php'));
require_once(app_file('login/elements.php'));

start_page('login');

$nonce = start_login_form("Password Reset","pwreset");

add_login_input("userid", array(
  'info' => 'Found in the login recovery email you should have received.',
));
add_login_input("token", array (
  'label' => 'Reset Token',
  'name' => 'token',
  'info' => 'Found in the login recovery email you should have received.',
));
add_login_input('new-password',array(
  'name'=>'password',
  'info'=>info_text('new-password'),
));

add_login_submit('Set Password','pwreset',true);

close_login_form();
end_page();

die();


