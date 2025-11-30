<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/users.php'));
require_once(app_file('include/status.php'));
require_once(app_file('include/responses.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/timestamps.php'));
require_once(app_file('survey/elements.php'));
require_once(app_file('survey/render.php'));

// Verify that there is an active survey
//   If not, display the "No survey" page
$active_id = active_survey_id();
if(!$active_id) {
  require(app_file('survey/no_survey.php'));
  die();
}

$userid    = active_userid() ?? null;
$content   = survey_content($active_id);

$navbar_args = [
  'title'  => active_survey_title(),
  'userid' => $userid,
];

$status = '';
$data   = [];
$draft_exists     = false;
$submitted_exists = false;

$reopen_submitted = ($_POST['action'] ?? '') === "reopen"; 

$responses = get_user_responses( $userid,$active_id);
$submitted = $responses['submitted'] ?? [];
$draft     = $responses['draft']     ?? [];
$state     = '';

$render_args = ['userid'=>$userid, 'survey_id'=>$active_id];

if($submitted && $draft)
{
  $state   = 'draft_updates';
  $render_args['responses'] = $draft['responses'];
  $render_args['feedback']  = $draft['feedback'];
  $navbar_args['submitted'] = $submitted['timestamp'];
  $navbar_args['draft']     = $draft['timestamp'];
}
elseif($submitted)
{
  $state     = 'submitted';
  $render_args['responses'] = $submitted['responses'];
  $render_args['feedback']  = $submitted['feedback'];
  $navbar_args['submitted'] = $submitted['timestamp'];
  $navbar_args['reopen']    = $reopen_submitted;
}
elseif($draft) {
  $state   = 'draft';
  $render_args['responses'] = $draft['responses'];
  $render_args['feedback']  = $draft['feedback'];
  $navbar_args['draft']     = $draft['timestamp'];
}
else {
  $state  = 'new';
}

$responses['state'] = $state;

start_survey_page($navbar_args);

if($submitted && !$draft && !$reopen_submitted)
{
  require_once(app_file('survey/submitted.php'));

  switch($_POST['action']??'') {
  case 'sendemail':
    if( 
      $userid &&
      (strtolower($_POST['userid'] ?? '') === $userid) &&
      ($user = User::from_userid($userid)) &&
      ($email = $user->email())
    ) {
      send_confirmation_email($userid,$active_id,$email,$content,$submitted);
    }

    show_submitted_page($userid,$active_id,$submitted['timestamp']);

    break;

  case 'withdraw':
    withdraw_responses($userid,$active_id);
    break;

  case 'restart':
    withdraw_and_restart($userid,$active_id);
    break;

  default:
    show_submitted_page($userid,$active_id,$submitted['timestamp']);
    break;
  }
}
else
{
  render_survey($state,$content,$render_args);

  $js_responses = json_encode($responses);
  echo "<script>";
  echo "const ttt_user_responses = $js_responses;";
  echo "</script>";
}

$user_menu = js_uri('user_menu','survey');
echo "<script type='module' src='$user_menu'></script>";

end_page();
die();
