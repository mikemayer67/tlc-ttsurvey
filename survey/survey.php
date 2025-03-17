<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/page_elements.php'));

log_dev("Loading Survey Page");

// Verify that there is an active survey
//   If not, display the "No survey" page
require_once(app_file('include/surveys.php'));
$active_survey_title = active_survey_title();
if(!$active_survey_title) {
  require(app_file('survey/no_survey.php'));
  die();
}

start_page('survey');

echo "<h1>SURVEY</h1>";

end_page();


