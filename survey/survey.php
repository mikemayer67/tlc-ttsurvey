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
$state  = 'new';
$data   = [];
$draft_exists     = false;
$submitted_exists = false;

$render_args = [ 
  'state'         => 'new',
  'has_draft'     => false,
  'has_submitted' => false,
];

$responses = get_user_responses( $userid,$active_id);
log_dev("Responses: ".print_r($responses,true));

if($responses) {
  $submitted = $responses['submitted'] ?? [];
  $draft     = $responses['draft']     ?? [];

  $state = $submitted 
    ? ( $draft ? 'draft_updates' : 'submitted' )
    : ( $draft ? 'draft'         : 'new'       );

  if($submitted) {
    $render_args['responses']    = $submitted['responses'];
    $ts      = recent_timestamp_string( $submitted['timestamp'] );
    $status .= "<div class='key'>Last Submitted</div><div class='timestamp'>$ts</div>";
  }
  else {
    $status .= "<div class='key'>Submitted</div><div class='timestamp'>---</div>";
  }

  if($draft) {
    $render_args['responses'] = $draft['responses'];
    $ts      = recent_timestamp_string($draft['timestamp']);
    $status .= "<div class='key'>Last Saved Draft</div><div class='timestamp'>$ts</div>";
  }
}

$responses['state']   = $state;

if($status) {
  $status = "<div class='survey-status'>$status</div>";
} else {
  $status = "Welcome";
}

start_survey_page($title,$userid,$status);
render_survey($state,$content,$render_args);

$js_data   = json_encode($responses);
echo "<script>const ttt_user_responses = $js_data;</script>";

$user_menu = js_uri('user_menu','survey');
echo "<script type='module' src='$user_menu'></script>";

end_page();
die();
