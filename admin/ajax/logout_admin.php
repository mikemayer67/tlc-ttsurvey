<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

validate_ajax_nonce('admin-navbar');

start_ob_logging();

log_info("Logging out Admin");

unset($_SESSION['admin-id']);

end_ob_logging();

echo json_encode(['success'=>true]);
die();
