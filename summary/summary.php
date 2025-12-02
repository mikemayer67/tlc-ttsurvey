<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/settings.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/responses.php'));
require_once(app_file('summary/elements.php'));

$admin_id = $_SESSION['admin-id'] ?? null;
$userid = active_userid();

$is_admin = $admin_id || in_array($userid, survey_admins());

$has_access = $is_admin || has_summary_access($userid);
if(!$has_access) { api_die(); }

$survey_id = $_REQUEST['summary'];
$active_id = active_survey_id();
if(!$survey_id) { $survey_id = $active_id; }

$info = survey_info($survey_id);
if(!$info) { api_die(); }

$summary_flags = (int)get_setting('summary_flags');
if($summary_flags & 2) { // requires submit
  if(!$is_admin) {
    if($survey_id === $active_id) {
      $responses = get_user_responses( $userid,$survey_id);
      $submitted = $responses['submitted'] ?? [];
      if(!$submitted) { $has_access = false; }
    }
  }
}

$title = $info['title'];

$content = survey_content($survey_id);
$sections = $content['sections'];
$tab_ids = array_map( function($section) { return $section['sequence']; }, $sections );

start_summary_page([
  'title' => $info['title'],
  'userid' => $userid,
  'tab_ids' => $tab_ids,
]);

if(!$has_access) { 
  echo "<br>You must submit your survey responses to unlock access to the summary";
  end_page();
  die();
}

echo "<div class='notebook'>";
$first = true;
foreach($sections as $section) {
  $seq  = $section['sequence'];
  $checked = $first ? 'checked' : '';
  $first = false;
  echo "<input id='tab-cb-$seq' class='tab-cb' type='radio' name='tab-cb' $checked>";
}

echo "<div class='tabs'>";
foreach($sections as $section) {
  $name = $section['name'];
  $seq  = $section['sequence'];
  echo "<label for='tab-cb-$seq' class='tab tab-$seq'>$name</label>";
}
echo "</div>";

foreach($sections as $section) {
  $name = $section['name'];
  $seq  = $section['sequence'];
  echo "<div id='panel-$seq' class='panel panel-$seq'>";
  echo "<h1>$name panel</h1>";
  echo "</div>";
}

echo "</div>"; // div.notebook

end_page();
die();
