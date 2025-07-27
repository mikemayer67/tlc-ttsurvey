<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once(app_file('vendor/autoload.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/users.php'));

handle_warnings();

$response = array("success"=>true);

try 
{
  // get required settings

  $smtp_host = $_POST['smtp_host'] ?? null;
  if(!$smtp_host) { throw new SMTPError('Missing smtp_host'); }

  $smtp_username = $_POST['smtp_username'] ?? null;
  if(!$smtp_username) { throw new SMTPError('Missing smtp_username'); }

  $smtp_password = $_POST['smtp_password'] ?? null;
  if(!$smtp_password) { throw new SMTPError('Missing smtp_password'); }

  // get optional settings

  $smtp_auth = $_POST['smtp_auth'] ?? 0;
  $smtp_auth = $smtp_auth ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;

  $smtp_port = $_POST['smtp_port'] ?? null;
  if(!$smtp_port) {
    $smtp_port = ($smtp_auth == PHPMailer::ENCRYPTION_STARTTLS) ? 587 : 465;
  }

  if($smtp_reply_email = $_POST['smtp_reply_email'] ?? null) {
    if(!filter_var($smtp_reply_email,FILTER_VALIDATE_EMAIL)) {
      throw new SMTPError('Invalid SMTP reply email address');
    }
  }

  $smtp_reply_name = $_POST['smtp_reply_name'] ?? null;

  // determine test email recipient

  if($test_email = $_POST['admin_email'] ?? null) {
    if(!filter_var($test_email,FILTER_VALIDATE_EMAIL)) {
      throw new SMTPError("Invalid admin email address");
    }
  }

  if(!$test_email) {
    if($userid = $_POST['primary_admin'] ?? null) {
      $user = USER::lookup($userid);
      if(!$user) { throw new SMTPError("Invalid primary admin userid"); }
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
      throw new SMTPError("No test email address");
    }
  }

  // attempt to send test email
  
  $mail = new PHPMailer(true);

  $mail->isSMTP();
  $mail->SMTPDebug  = 2;
  $mail->Host       = $smtp_host;
  $mail->Port       = $smtp_port;
  $mail->SMTPSecure = $smtp_auth;
  $mail->SMTPAuth   = true;
  $mail->Username   = $smtp_username;
  $mail->Password   = $smtp_password;

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

  ob_start();
  $mail->send();
} 
catch(SMTPError $e) {
  log_info("SMTP Error: ". $e->getMessage());
  $response = array('success'=>false, 'reason'=>$e->getMessage());
}
catch(Exception $e) {
  log_warning("Failed Exception: ". $e->getMessage());
  $response = array('success'=>false, 'reason'=>$e->getMessage());
}
finally {
  log_info("SMTP logging:\n".ob_get_contents());
  ob_end_clean();
}

echo json_encode($response);
die();
