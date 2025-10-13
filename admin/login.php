<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('login/elements.php'));

log_dev("------------------ Start of Admin Login -------------------");

start_admin_page();

$admin_login_requested = ($_REQUEST['admin']??'') === 'login';

$form_uri = app_uri('admin');
$cancel_uri = $admin_login_requested ? app_uri('admin') : app_uri();

echo "<div id='ttt-login'>";
echo "<form id='admin-login' method='post' action='$form_uri'>";
add_hidden_input('nonce',gen_nonce('admin-login'));
add_hidden_input('ajaxuri',app_uri());
add_hidden_input('cancel',$cancel_uri);
add_hidden_submit('action','submit');

echo "<header>Admin Login</header>";

echo "<div class='login-box'>";
add_login_input("userid");
add_login_input("password");
add_login_submit("Log in","admin",true);
echo "</div>";

echo "</form>";
echo "</div>";

echo "<script src='", js_uri('login','admin'), "'></script>";

end_page();
die();
  
