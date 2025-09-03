<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/status.php'));
require_once(app_file('include/elements.php'));
require_once(app_file('survey/render.php'));

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

start_page('survey',[
  'survey_title'=>$title,
]);

render_survey($user,$content);

end_page();


