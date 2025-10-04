<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

// perform all of the login valdation stuff
require(app_file('survey/ajax/validate.php'));

require_once(app_file('include/sendmail.php'));

start_ob_logging();
$userid = $_POST['userid'];
$email  = $_POST['email'];
sendmail_profile($email,$userid,'password','(undisclosed)','(undisclosed)');
end_ob_logging();

http_response_code(200);
regen_active_token();

die();
