<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/page_elements.php'));

log_dev("-------------- Start of Admin Dashboard --------------");

$admin_id = $_SESSION['admin-id'] ?? null;
if( $admin_id && ($user = User::lookup($admin_id)) ) {
  if(!verify_role($admin_id,'admin')) { 
    log_warning("Invalid admin login attempt with admin id: $admin_id");
    $admin_id = '';
    unset($_SESSION['admin-id']);
  }
}

if(!$admin_id) {
  require(app_file('login/admin.php'));
  die();
}

start_page('admin');

echo "<h1>ADMIN</h1>";

end_page();


