<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('admin/surveys/update.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-surveys');

start_ob_logging();

// using javascript key id rather than PHP key survey_id
$survey_id  = $_POST['id'] ?? null;
$title      = $_POST['name'] ?? null;
$content    = json_decode($_POST['content'],true);

if(!$survey_id)  { send_ajax_bad_request('Missing survey_id in request'); }

update_survey($survey_id,$content,$title);

$next_ids        = next_survey_ids($survey_id);
$revised_content = survey_content($survey_id);

end_ob_logging();

$response = new AjaxResponse();
$response->add('content', $revised_content);
$response->add('next_ids',$next_ids);
$response->send();

die();
