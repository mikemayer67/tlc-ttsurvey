<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

validate_ajax_nonce('admin-navbar');

log_info("Logging out Admin");

unset($_SESSION['admin-id']);

echo json_encode(['success'=>true]);
die();
