<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/status.php'));
require_once(app_file('include/elements.php'));
require_once(app_file('include/responses.php'));
require_once(app_file('include/timestamps.php'));
require_once(app_file('survey/render.php'));

// Verify that there is an active survey
//   If not, display the "No survey" page
require_once(app_file('include/surveys.php'));
$active_id = active_survey_id();
if(!$active_id) {
  require(app_file('survey/no_survey.php'));
  die();
}

$title     = active_survey_title();
$content   = survey_content($active_id);
$userid    = active_userid() ?? null;

$status = '';
$key    = null;
$data   = null;

$responses = get_user_responses( $userid,$active_id);

log_dev("Responses: ".print_r($responses,true));

if($responses) {
  $submitted = $responses['submitted'] ?? [];
  $draft     = $responses['draft'] ?? [];
  if($submitted) {
    $responses['key'] = 'submitted';
    $data   = $submitted['responses'];
    $ts     = $submitted['timestamp'];
    $ts     = recent_timestamp_string($ts);
    $status .= "<div class='key'>Last Submitted</div><div class='timestamp'>$ts</div>";

  }
  if($draft) {
    $responses['key'] = 'draft';
    $data   = $draft['responses'];
    $ts     = $draft['timestamp'];
    $ts     = recent_timestamp_string($ts);
    $status .= "<div class='key'>Unsubmitted Edits</div><div class='timestamp'>$ts</div>";
  }
}

if($status) {
  $status = "<div class='survey-status'>$status</div>";
}

start_survey_page($title,$userid,$status);
render_survey($userid,$content,['responses'=>$data]);

$responses['key'] = $key;
$js_data   = json_encode($responses);
echo "<script>const ttt_user_responses = $js_data;</script>";

$user_menu = js_uri('user_menu','survey');
echo "<script type='module' src='$user_menu'></script>";

end_page();
die();
