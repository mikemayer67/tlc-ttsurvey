<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('survey/elements.php'));

// check for updates from a different session

$userid    = $_POST['userid'];
$survey_id = $_POST['survey_id'];
$timestamps = user_status_timestamps($userid,$survey_id);

$change = compare_status_timestamps($timestamps);

http_response_code(200);
echo json_encode([
  'success' => true,
  'modified' => $change['modified'] ?? '',
  'new_timestamps' => $timestamps,
]);
die();