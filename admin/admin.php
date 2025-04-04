<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/page_elements.php'));
require_once(app_file('admin/elements.php'));

log_dev("-------------- Start of Admin Dashboard --------------");

log_dev("POST = ".print_r($_POST,true));

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

$tab = $_REQUEST['tab'] ?? 'settings';

start_page('admin');
start_admin_page($tab);

require(app_file("admin/{$tab}_tab.php"));

end_admin_page();
end_page();


