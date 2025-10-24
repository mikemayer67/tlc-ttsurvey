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
$data   = [];
$draft_exists     = false;
$submitted_exists = false;

$reopen_submitted = false;

$responses = get_user_responses( $userid,$active_id);
$submitted = $responses['submitted'] ?? [];
$draft     = $responses['draft']     ?? [];

if($submitted && $draft)
{
  $render_args['state']     = 'draft_updates';
  $render_args['responses'] = $draft['responses'];
  $ts1     = recent_timestamp_string( $submitted['timestamp'] );
  $ts2     = recent_timestamp_string($draft['timestamp']);
  $status  = "<div class='survey-status'>";
  $status .= "<div class='key'>Last Submitted</div><div class='timestamp'>$ts1</div>";
  $status .= "<div class='key'>Last Saved Draft</div><div class='timestamp'>$ts2</div>";
  $status .= "</div>";
}
elseif($submitted)
{
  $render_args['state']     = 'submitted';
  $render_args['responses'] = $submitted['responses'];
  if($reopen_submitted) {
    $ts      = recent_timestamp_string( $submitted['timestamp'] );
    $status  = "<div class='survey-status'>";
    $status .= "<div class='key'>Last Submitted</div><div class='timestamp'>$ts</div>";
    $status .= "</div>";
  } else {
    $status = 'Thank You';
  }

}
elseif($draft) {
  $render_args['state']     = 'draft';
  $render_args['responses'] = $draft['responses'];
  $ts      = recent_timestamp_string($draft['timestamp']);
  $status  = "<div class='survey-status'>";
  $status .= "<div class='key'>Submitted</div><div class='timestamp'></div>";
  $status .= "<div class='key'>Last Saved Draft</div><div class='timestamp'>$ts</div>";
  $status .= "</div>";
}
else {
  $render_args['state'] = 'new';
  $status = "Welcome";
}

$responses['state']   = $render_args['state'];

start_survey_page($title,$userid,$status);

if($submitted && !$draft && !$reopen_submitted)
{
  require_once(app_file('survey/submitted.php'));
  show_submitted_page($userid,$content,$submitted);
}
else
{
  render_survey($render_args['state'],$content,$render_args);

  $js_data   = json_encode($responses);
  echo "<script>const ttt_user_responses = $js_data;</script>";
}

$user_menu = js_uri('user_menu','survey');
echo "<script type='module' src='$user_menu'></script>";

end_page();
die();
