<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-surveys');

start_ob_logging();

$id = $_POST['survey_id'];

// assume failure unless content was actually found
$response = new AjaxResponse(false);

$content = survey_content($id);
if($content) { 
  $response->succeed();
  $response->add('content',$content);
}

end_ob_logging();

$response->send();
die();
