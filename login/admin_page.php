<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/page_elements.php'));
require_once(app_file('include/redirect.php'));
require_once(app_file('login/elements.php'));

start_page("login");

$nonce = start_login_form("Admin Login","admin");
add_hidden_submit('action','admin');

$redirect_data = get_redirect_data();
$userid   = $redirect_data['userid']   ?? null;

add_login_input("userid", array('value' => $userid) );
add_login_input("password");
add_login_submit("Log in","admin");

close_login_form();

end_page();
