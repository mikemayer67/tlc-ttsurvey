<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));
require_once(app_file('include/responses.php'));

$action = $_POST['action'] ?? 'cancel';

log_dev("-------------- Handle Submit: $action --------------");

// Go ahead and handle the cancel button immediately by simply going back to the main entry point
if($action === 'cancel')
{
  header('Location: '.app_uri());
  die();
}

// validate that all credentials are in order before proceeding

validate_nonce('survey-form');

$active_userid    = active_userid() ?? null;
$submitted_userid = $_POST['userid'] ?? null;
if(is_null($active_userid))    { validation_error("Attempt to submit responses without being logged in"); }
if(is_null($submitted_userid)) { validation_error("Attempt to submit responses without a userid"); }

if($active_userid !== $submitted_userid) { 
  validation_error("Attempt to submit responses as $submitted_userid when active user is $active_userid");
}

$active_survey_id    = active_survey_id() ?? null;
$submitted_survey_id = $_POST['survey_id'] ?? null;
if(is_null($active_survey_id))    { validation_error("Attempt to submit responses without an active survey"); }
if(is_null($submitted_survey_id)) { validation_error("Attempt to submit responses without a survey_id"); }

$active_survey_id    = intval($active_survey_id);
$submitted_survey_id = intval($submitted_survey_id);
if(intval($active_survey_id) !== intval($submitted_survey_id)) {
  validation_error("Attempt to submit responses for survey #$submitted_survey_id when active survey is #$active_survey_id");
}

$result = update_user_responses($submitted_userid,$submitted_survey_id,$action,$_POST);

if(empty($_POST['js_enabled'])) {
  // We cannot use javascript to send an async AJAX call to send the email, so we need
  // to handle that now.  Non-JS users will just have to deal with the lag this causes.
  require_once(app_file('include/users.php'));
  $active_user = User::from_userid($active_userid);
  $email = $active_user->email();
  if($email) {
    todo("replace logging with function call to send email");
    log_dev("Send email notification to $email (no javascript on browser)");
  }
  $_SESSION['queued-confirmation-email']=false;
} else {
  $_SESSION['queued-confirmation-email']=true;
}

header('Location: '.app_uri());
die();
