<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('admin/surveys/update.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-surveys');

start_ob_logging();

$survey_id  = $_POST['survey_id'] ?? null;
$new_state  = $_POST['new_state'] ?? null;

if (!$survey_id) { send_ajax_bad_request('missing survey_id'); }
if (!$new_state) { send_ajax_bad_request('missing new_state'); }

$message = '';
$success = update_survey_state($survey_id, $new_state, $message);
log_info($message);

if(!$success) { send_ajax_failure($message); }

end_ob_logging();

$response = new AjaxResponse();
$response->add('message', $message);
$response->send();

die();