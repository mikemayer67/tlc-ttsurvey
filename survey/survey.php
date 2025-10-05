<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/status.php'));
require_once(app_file('include/elements.php'));
require_once(app_file('survey/render.php'));
require_once(app_file('login/elements.php'));

todo("Flesh out survey page");

// Verify that there is an active survey
//   If not, display the "No survey" page
require_once(app_file('include/surveys.php'));
$active_id = active_survey_id();
if(!$active_id) {
  require(app_file('survey/no_survey.php'));
  die();
}

$title   = active_survey_title();
$content = survey_content($active_id);
$userid  = active_userid();
$user    = User::from_userid($userid);

$icons = [
  'show' => img_uri('icon8/show_pw.png'),
  'hide' => img_uri('icon8/hide_pw.png'),
];

$hints = [
  'name'     => login_info_string('fullname'),
  'email'    => login_info_string('email'),
  'password' => login_info_string('password'),
];

$user_info = [
  'userid' => $userid,
  'name'   => $user->fullname(),
  'email'  => $user->email(),
];

echo "<script>";
echo "const ttt_icons = ".json_encode($icons).";";
echo "const ttt_hints = ".json_encode($hints).";";
echo "const ttt_user = ".json_encode($user_info).";";
echo "const ttt_preview = false;";
echo "</script>";

start_page('survey', [
  'survey_title'=>$title,
  'status'=>'[status goes here]',
]);

render_survey($user,$content);

end_page();


