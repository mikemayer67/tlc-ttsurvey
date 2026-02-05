<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('admin/surveys/create_new.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-surveys');

$name = $_POST['name'] ?? null;
if(!$name) { send_ajax_bad_request('missing name'); }

start_ob_logging();

$clone = $_POST['clone'] ?? null;

$error = '';
$new_id = create_new_survey($name,$clone,$error);
if(!$new_id) { send_ajax_failure($error); }

$info = survey_info($new_id);

end_ob_logging();

$response = new AjaxResponse();
$response->add('survey', $info);
$response->send();

die();
