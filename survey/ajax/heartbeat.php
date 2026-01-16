<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/login.php'));

$userid = active_userid();
$token  = active_token();

if(!$_SESSION) {
  // session has expired... let's jump start it
  // @@@ MAM... this is a hack to address the timeout issue
  //   it needs to be replaced with some actual hardening of the code
  $_SESSION['active-userid'] = $userid;
  $_SESSION['active-token'] = $token;
  $_SESSION['nonce'] = ['survey-form'=>$_POST['nonce']];

  log_dev("Clear... hand me the paddles... restarting session for $userid");
}

http_response_code(200);
echo json_encode(['success'=>true]);
die();