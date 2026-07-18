<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/survey_content.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-surveys');

start_ob_logging();

$id = parse_ajax_integer_input('survey_id');

// assume failure unless content was actually found
$response = new AjaxResponse(false);

$content = new SurveyContent($id);
if($content->is_empty()) {
  send_ajax_failure("No survey content found for id=$id");
}

end_ob_logging();

$response = new AjaxResponse();
$response->add('content',$content->as_array());
$response->send();

die();
