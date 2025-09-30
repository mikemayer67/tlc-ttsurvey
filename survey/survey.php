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
$user    = active_userid();

$show_pw_icon = json_encode(img_uri('icons8/show_pw.png'));
$hide_pw_icon = json_encode(img_uri('icons8/hide_pw.png'));
$name_hint    = json_encode(login_info_string('fullname'));
$email_hint   = json_encode(login_info_string('email')   );
$passwd_hint  = json_encode(login_info_string('password'));
echo <<<SCRIPT
<script>
  const ttt_show_pw_icon='$show_pw_icon';
  const ttt_hide_pw_icon='$hide_pw_icon';
  const ttt_preview = false;
  const ttt_name_hint='$name_hint';
  const ttt_email_hint='$email_hint';
  const ttt_password_hint='$passwd_hint';
</script>
SCRIPT;

start_page('survey', [
  'survey_title'=>$title,
  'status'=>'[status goes here]',
]);

render_survey($user,$content);

end_page();


