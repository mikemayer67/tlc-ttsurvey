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

// determine if the current user has access to see summaries in general
//   If not, boot them out now
$has_access = $is_admin || has_summary_access($userid);
if(!$has_access) { api_die(); }

$survey_id = $_REQUEST['summary'];
$active_id = active_survey_id();
if(!$survey_id) { $survey_id = $active_id; }

// determine if the current user has access to this particular summary
// - If they are an admin, then yes
// - If there is no requirement to have submitted your survey first, then yes
// - If the request was for a past (closed) survey, then yes
// - If they have sumbitted their survey, then yes
// - Otherwise, no
$summary_flags = (int)get_setting('summary_flags');
if($summary_flags & 2) { // requires submit
  if(!$is_admin) {
    if($survey_id && ($survey_id === $active_id)) {
      $responses = get_user_responses( $userid,$survey_id);
      $submitted = $responses['submitted'] ?? [];
      if(!$submitted) { $has_access = false; }
    }
  }
}

$content   = survey_content($survey_id);
$responses = get_all_responses($survey_id);
$sections = [];
foreach($content['questions'] as $question) {
  if(strtolower($question['type']??'') !=='info') {
    $sid = $question['section'] ?? null;
    if($sid && !array_key_exists($sid,$sections)) {
      $section = $content['sections'][$sid];
      if($section) { $sections[$sid] = $section; }
    }
  }
}
$sections = array_values($sections);
usort($sections, fn($a,$b) => $a['sequence'] <=> $b['sequence']);

$tab_ids = array_map( function($section) { return $section['section_id']; }, $sections );

$info = survey_info($survey_id);
$title = $info['title']??null;

start_summary_page([
  'title' => $title,
  'userid' => $userid,
  'tab_ids' => $tab_ids,
]);

if(!$info) {
  echo "<br>There are currently no active survey";
  end_page();
  die();
}

if(!$has_access) { 
  echo "<br>You must submit your survey responses to unlock access to the summary";
  end_page();
  die();
}

echo "<div class='notebook'>";
$first = true;
foreach($sections as $section) {
  $sid  = $section['section_id'];
  $checked = $first ? 'checked' : '';
  $first = false;
  echo "<input id='tab-cb-$sid' class='tab-cb' type='radio' name='tab-cb' $checked>";
}

echo "<div class='tabs'>";
foreach($sections as $section) {
  $sid  = $section['section_id'];
  $name = $section['name'];
  echo "<label for='tab-cb-$sid' class='tab tab-$sid'>$name</label>";
}
echo "</div>";

foreach($tab_ids as $sid) {
  add_section_panel($sid,$content,$responses);
}

echo "</div>"; // div.notebook

end_page();
die();
