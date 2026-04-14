<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/cookiejar.php'));
require_once(app_file('include/sendmail.php'));

function process_bug_report()
{
  $errid = $_POST['errid'] ?? null;
  $usermsg = $_POST['user-input'] ?? null;
  $errmsg = $_SESSION['internal-error'][$errid] ?? null;
  $reporter = active_userid();

  $issue_url = null;

  sendmail_bug_report($errid, $errmsg, $usermsg, $reporter, $issue_url);
}

// The nonce validation here is to ensure we don't get bombarded with fraudulent
//  but reports.  In this case, we want to silently fail.  We won't send a bug
//  report to the admins or open a new issue on Github.  But... we won't tell 
//  the user that the report was silently ignored either.  We'll simply 
//  thank them and return to the app's main entry point.

set_info_status('Thank you for reporting the issue');

if(validate_nonce('bug-reporting',dieonfail:false)) { process_bug_report(); }

