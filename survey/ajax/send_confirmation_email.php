<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/ajax.php'));

require_once(app_file('survey/submitted.php'));  // for send_confirmation_email

start_ob_logging();

// Validate the request before doing anything else

$active_id = active_survey_id();
if(!$active_id) { send_ajax_bad_request('no active survey'); }

$active_userid = active_userid() ?? null;
if(!$active_userid) { send_ajax_unauthorized('no active userid'); }

$userid = strtolower($_POST['userid'] ?? '');
if($userid === '') { send_ajax_bad_request('no userid in POST'); }

$user = User::from_userid($userid);
if(!$user) { send_ajax_bad_request("invalid userid ($userid)"); }

if($userid !== $active_userid) { send_ajax_bad_request('$userid is not the active userid'); }

$email = $user->email();
if(!$email) { send_ajax_failure('no email address in your profile'); }

$content   = survey_content($active_id);
if(!$content) { send_ajax_bad_request("No content data found for survey $active_id"); }

require_once(app_file('include/responses.php'));

$submitted = get_user_responses($userid,$active_id,0);
if(!$submitted) { send_ajax_failure("No submitted responses found for $userid"); }

$response = new AjaxResponse();
$response->add('email',$email);

$success = send_confirmation_email($userid,$active_id,$email,$content,$submitted);
if(!$success) { $response->fail(); }

end_ob_logging();

$response->send();

die();
