<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/status.php'));
require_once(app_file('include/elements.php'));
require_once(app_file('include/responses.php'));
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

$status    = 'New Survey';
$key       = null;

$responses = get_user_responses( $userid,$active_id);

if($responses) {
  if( array_key_exists('draft',$responses) ) {
    $key    = 'draft';
    $status = 'Unsubmitted Responses';
  } elseif(array_key_exists('submitted',$responses) ) {
    $key    = 'filed';
    $status = 'Submitted Responses';
  }
}

$data = $responses[$key]['responses'] ?? null;

start_survey_page($title,$userid,$status);
render_survey($userid,$content,['responses'=>$data]);

$responses['key'] = $key;
$js_data   = json_encode($responses);
echo "<script>const ttt_user_responses = $js_data;</script>";

$user_menu = js_uri('user_menu','survey');
echo "<script type='module' src='$user_menu'></script>";

end_page();
die();
