<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-surveys');

start_ob_logging();

$id = parse_ajax_integer_input('survey_id');

// assume failure unless content was actually found
$response = new AjaxResponse(false);

$content = survey_content($id);
if(!$content['sections']) { send_ajax_failure("No survey content found for id=$id"); }

end_ob_logging();

$response = new AjaxResponse();
$response->add('content',$content);
$response->send();

die();
