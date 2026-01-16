<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('admin/surveys/create_new.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-surveys');

$name = $_POST['name'] ?? null;
if(!$name) { send_ajax_bad_request('missing name'); }

$response = new AjaxResponse();

start_ob_logging();

$clone = $_POST['clone'] ?? null;

$error = null;
$new_id = create_new_survey($name,$clone,$error);

if($new_id) {
  $info = survey_info($new_id);
  $response->add('survey',$info);
} else {
  $response->fail($error);
}

end_ob_logging();

$response->send();
die();
