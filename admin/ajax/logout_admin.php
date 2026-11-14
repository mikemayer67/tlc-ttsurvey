<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('common/logger.php'));
require_once(app_file('common/ajax.php'));

validate_ajax_nonce('admin');

start_ob_logging();

log_info("Logging out Admin");

unset($_SESSION['admin-id']);

end_ob_logging();

$response = new AjaxResponse();
$response->send();

die();
