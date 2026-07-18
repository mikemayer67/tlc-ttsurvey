<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('admin/update_survey.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-surveys');

start_ob_logging();

$survey_id  = $_POST['survey_id'] ?? null;
$new_state  = $_POST['new_state'] ?? null;

if (!$survey_id) { send_ajax_bad_request('missing survey_id'); }
if (!$new_state) { send_ajax_bad_request('missing new_state'); }

$old_state = get_survey_state($survey_id);
if( !$old_state ) { send_ajax_bad_request("invalid survey_id"); }

if( $old_state === $new_state) { send_ajax_failure("survey state already $new_state"); }

if($new_state === 'draft') {
  $query = 'UPDATE tlc_srv_surveys SET active=NULL, closed=NULL WHERE survey_id=?';
} elseif($new_state === 'active') {
  $query = 'UPDATE tlc_srv_surveys SET active=CURRENT_TIMESTAMP, closed=NULL WHERE survey_id=?';
} elseif($new_state === 'closed') {
  $query = 'UPDATE tlc_srv_surveys SET closed=CURRENT_TIMESTAMP WHERE survey_id=?';
} else {
  send_ajax_bad_request("invalid state: $new_state");
}
MySQLExecuteWithExceptionHandler(
  $query,
  function($e,$p) use ($new_state) {
    send_ajax_internal_error(
      sprintf('Failed to change state of survey %d to %s: %s', $p[0], $new_state, $e->getMessage())
    );
  },
  'i',$survey_id
);

end_ob_logging();

log_info("state of survey $survey_id changed to $new_state");

$response = new AjaxResponse();
$response->add('message', "survey state changed to $new_state");
$response->send();

die();