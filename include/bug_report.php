<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

function process_bug_report()
{
  log_dev("__process_bug_report__");
}

// The nonce validation here is to ensure we don't get bombarded with fraudulent
//  but reports.  In this case, we want to silently fail.  We won't send a bug
//  report to the admins or open a new issue on Github.  But... we won't tell 
//  the user that the report was silently ignored either.  We'll simply 
//  thank them and return to the app's main entry point.

set_info_status('Thank you for the bug report');

if(validate_nonce('bug-reporting',dieonfail:false)) { process_bug_report(); }

