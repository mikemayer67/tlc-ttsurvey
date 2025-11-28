<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/settings.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/responses.php'));

$admin_id = $_SESSION['admin-id'] ?? null;
$userid = active_userid();

$is_admin = $admin_id || in_array($userid, survey_admins());

$has_access = $is_admin || has_summary_access($userid);
if(!$has_access) { api_die(); }

$survey_id = $_REQUEST['summary'];
$active_id = active_survey_id();
if(!$survey_id) { $survey_id = $active_id; }

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

echo "Welcome to the Summary $survey_id";

if(!$has_access) { 
  echo "<br>You must submit your survey responses to unlock access to the summary";
}


die();
