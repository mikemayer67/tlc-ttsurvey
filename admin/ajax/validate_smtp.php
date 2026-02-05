<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once(app_file('vendor/autoload.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/ajax.php'));

handle_warnings();

// get required settings

$smtp_host = $_POST['smtp_host'] ?? null;
if(!$smtp_host) { send_ajax_failure('Missing smtp_host'); }

$smtp_username = $_POST['smtp_username'] ?? null;
if(!$smtp_username) { send_ajax_failure('Missing smtp_username'); }

$smtp_password = $_POST['smtp_password'] ?? null;
if(!$smtp_password) { send_ajax_failure('Missing smtp_password'); }

// get optional settings

$smtp_auth = $_POST['smtp_auth'] ?? 0;
$smtp_auth = $smtp_auth ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;

$smtp_port = $_POST['smtp_port'] ?? null;
if(!$smtp_port) {
  $smtp_port = ($smtp_auth == PHPMailer::ENCRYPTION_STARTTLS) ? 587 : 465;
}

if($smtp_reply_email = $_POST['smtp_reply_email'] ?? null) {
  if(!filter_var($smtp_reply_email,FILTER_VALIDATE_EMAIL)) {
    send_ajax_failure('Invalid SMTP reply email address');
  }
}

$smtp_reply_name = $_POST['smtp_reply_name'] ?? null;

// determine test email recipient

if($test_email = $_POST['admin_email'] ?? null) {
  if(!filter_var($test_email,FILTER_VALIDATE_EMAIL)) {
    send_ajax_failure("Invalid admin email address");
  }
}

if(!$test_email) {
  if($userid = $_POST['primary_admin'] ?? null) {
    $userid = strtolower($userid);

    $user = USER::lookup($userid);
    if(!$user) { send_ajax_failure("Invalid primary admin userid"); }
    if($email = $user->email()) {
      $email = filter_var($email,FILTER_VALIDATE_EMAIL);
      if($email) { $test_email = $email; }
    }
  }
}

if(!$test_email) {
  if(filter_var($smtp_username,FILTER_VALIDATE_EMAIL)) {
    $test_email = $smtp_username;
  } else {
    send_ajax_failure("No test email address");
  }
}
  // attempt to send test email
  
start_ob_logging();
$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->SMTPDebug  = 2;
$mail->Host       = $smtp_host;
$mail->Port       = $smtp_port;
$mail->SMTPSecure = $smtp_auth;
$mail->SMTPAuth   = true;
$mail->Username   = $smtp_username;
$mail->Password   = $smtp_password;
$mail->Timeout    = 10; // seconds

if($smtp_reply_email && $smtp_reply_name) {
  $mail->addReplyTo($smtp_reply_email,$smtp_reply_name);
} elseif($smtp_reply_email) {
  $mail->addReplyTo($smtp_reply_email);
} elseif($smtp_reply_name) {
  $mail->addReplyTo($smtp_username,$smtp_reply_name);
}

$from_address = $smtp_reply_email ? $smtp_reply_email : $smtp_username;
$from_name    = $smtp_reply_name  ? $smtp_reply_name  : 'SMTPTest';
$mail->setFrom($from_address,$from_name);

$mail->addAddress($test_email);
$mail->Subject = "SMTP Test";

$mail->isHTML(false);
$mail->Body = "SMTP Test Email";

try {
  $mail->send();
} 
catch(Exception $e) {
  log_warning("SMTP failed: ". $e->getMessage());
  ob_end_clean();
  send_ajax_failure($e->getMessage());
}
$smtp_output = ob_get_contents();
log_info("SMTP logging:\n$smtp_output");
ob_end_clean();

$response = new AjaxResponse();
$response->send();
die();
