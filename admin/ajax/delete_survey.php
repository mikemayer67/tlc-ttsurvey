<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-maintenance');
$response = new AjaxResponse();

start_ob_logging();

$survey_id = $_POST['survey_id'];

$result = MySQLExecuteWithExceptionHandler(
  'delete from tlc_srv_surveys where survey_id=?',
  function($e,$p) { send_ajax_internal_error($e->getMessage()); },
  'i', $survey_id
);

if($result) {
  $response->succeed();
} else {
  $response->fail("Not found in database");
}

end_ob_logging();

$response->send();
die();