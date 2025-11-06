<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/surveys.php'));

class ValidationError extends \Exception {}
class FailedToSend    extends \Exception {}

function fail_to_send($reason)
{
  $response = ['success'=>false, 'reason'=>$reason];
  echo json_encode($response);
  die();
}

$response = ['success'=>true];

try {
  start_ob_logging();

  // Validate the request before doing anything else
  //   log the validation failure and return http code 405

  $active_id = active_survey_id();
  if(!$active_id) { throw new ValidationError('no active survey'); }

  $active_userid = active_userid() ?? null;
  if(!$active_userid) { fail_vlidation('no active userid'); }

  $userid = $_POST['userid'] ?? null;
  if(!$userid) { throw new ValidationError('no userid in POST'); }

  $user = User::from_userid($userid);
  if(!$user) { throw new ValidationError("invalid userid ($userid)"); }

  if($userid !== $active_userid) { throw new ValidationError('$userid is not the active userid'); }

  $email = $user->email();
  if(!$email) { throw new FailedToSend('no email address in your profile'); }

  $content   = survey_content($active_id);
  if(!$content) { throw new ValidationError("No content data found for survey $active_id"); }

  require_once(app_file('include/responses.php'));

  $submitted = get_user_responses($userid,$active_id,0);
  if(!$submitted) { throw new ValidationError("No submitted responses found for $userid"); }

  $response['email'] = $email;

  require_once(app_file('survey/submitted.php'));

  // Passed validation
  //   Time to draft the email

  send_confirmation_email($userid,$active_id,$email,$content,$submitted);
}
catch(ValidationError $e) {
  log_warn("Failed to validate: $failure");
  http_response_code(405);
  die();
}
catch(FailedToSend $e) {
  $response['success'] = false;
  $response['reason'] = $e->getMessage();
}
finally {
  end_ob_logging();
}

echo json_encode($response);
die();
