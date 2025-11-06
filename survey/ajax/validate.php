<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/login.php'));

validate_ajax_nonce('survey-form');

// Validate that the login info on the client (cookie) matches the
//   login info on the server (session).
// Validate that the login info in the AJAX call matches the session login.
//
// If any failure occurs, present the ambiguous 405 API error page to obfuscate failure reason.

$active_userid = active_userid();
$session_userid = $_SESSION['active-userid'] ?? '(none)';

if( $session_userid !== $active_userid ) {
  log_warning("Invalid userid in ajax validation: session=$session_userid, cookie=$active_userid");
  http_response_code(405);
  die();
}

$active_token = active_token();
$session_token = $_SESSION['active-token'] ?? '(none)';

if( $session_token !== $active_token ) {
  log_warning("Invalid token in ajax validation: session=$session_token, cookie=$active_token");
  http_response_code(405);
  die();
}

$userid = $_POST['userid'];

if($userid !== $session_userid) {
  log_warning("Invalid userid in ajax validation: session=$session_userid, ajax=$userid");
  http_response_code(405);
  die();
}

