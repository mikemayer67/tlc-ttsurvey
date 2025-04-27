<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('admin/admin_lock.php'));

$lock = obtain_admin_lock();

echo json_encode($lock);
die();
