<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/ajax.php'));

// The active userid and token come from the cookies sent with the AJAX request
//   Verify that these are still valid credentials.
$active_userid = active_userid();
$active_token  = active_token();

if( !validate_access_token($active_userid, $active_token)) {
  log_warning("Invalid userid/token in ajax validation: userid=$active_userid");
  send_ajax_unauthorized('invalid userid/token');
}

$post_userid = $_POST['userid'] ?? null;
if($post_userid && ($post_userid !== $active_userid)) {
  log_warning("The userid in AJAX request ($post_userid) differs from authenticated userid ($active_userid)");
  send_ajax_bad_request('not active userid');
}