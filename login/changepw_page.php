<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));
require_once(app_file('login/elements.php'));

start_login_page('login');

$nonce = start_login_form("Change Password","changepw");

close_login_form();
end_page();

die();

