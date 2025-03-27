<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once(app_file('vendor/autoload.php'));
require_once(app_file('include/logger.php'));

function sendmail($email,$subject,$text,$html=null)
{
  if(!$html) { $html = $text; }

  $mail = new PHPMailer(true);

  try {
    //Server settings
    $mail->isSMTP();
    $mail->SMTPDebug  = SMTP_DEBUG;
    $mail->Host       = SMTP_HOST;
    $mail->Port       = SMTP_PORT;
    $mail->SMTPSecure = SMTP_AUTH ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;

    $mail->setFrom( SMTP_REPLY_TO ? SMTP_REPLY_TO : SMTP_USERNAME, APP_NAME );

    if(SMTP_REPLY_TO && SMTP_REPLY_NAME) {
      $mail->addReplyTo(SMTP_REPLY_TO,SMTP_REPLY_NAME);
    } elseif(SMTP_REPLY_TO) {
      $mail->addReplyTo(SMTP_REPLY_TO);
    } elseif(SMTP_REPLY_NAME) {
      $mail->addReplyTo(SMTP_USERNAME,SMTP_REPLY_NAME);
    }

    $mail->addAddress($email);
    $mail->Subject = $subject;

    if($html) {
      $mail->isHTML(true);
      $mail->Body    = $html;
      $mail->AltBody = $text;
    } else {
      $mail->isHTML(false);
      $mail->Body = $text;
    }

    ob_start();
    $mail->send();

    log_dev('Message has been sent');
    return true;
  } 
  catch (Exception $e) 
  {
    todo("Add sendmail failure to displayed status");
    log_dev("Failed to send email {$mail->ErrorInfo}");
    return false;
  }
  finally
  {
    log_dev("SMTP logging:\n".ob_get_contents());
    ob_end_clean();
  }
}

//------------------------------------------------
// Profile Update Notice
//------------------------------------------------

function sendmail_profile($email,$userid,$change,$old_value,$new_value)
{
  log_dev("sendmail_profile($email,$userid,$change,$old_value,$new_value)");

  $html  = "<div style='font-weight:bolder;'>";
  $html .= "A change has been made to the profile associated with userid: $userid";
  $html .= "</div>";
  $html .= "<div style='margin-left:1em;'>";
  $html .= "<ul>";
  $html .= "<li>Old $change: $old_value</li>";
  $html .= "<li>New $change: $new_value</li>";
  $html .= "</ul>";
  $html .= "</div>";
  $html .= "<br>";
  $html .= "<div>If you did not make this change, please contact one of the following:</div>";
  $html .= "<div style='margin-left:1em;'>";
  $html .= html_contacts(); 

  $text = <<<TEXT
A change has been made to the profile associated with userid: $userid"

  Old $change: $old_value
  New $change: $new_value

If you did not make this change, please contact one of the following:
TEXT;
  $text .= text_contacts(); 

  return sendmail($email, "Profile Update", $text, $html);
}

//------------------------------------------------
// Login Recover Information
//------------------------------------------------

function sendmail_recovery($email,$tokens)
{
  log_dev("sendmail_recovery($email,...)\n".print_r($tokens,true));
  $ntokens = count($tokens);

  $html  = "<div style='margin-left:1em;'>";
  $html .= "<p>Here is the login recovery information you requested:</p>";

  $text = "Here is the login recovery information you requested:\n";

  $s = '';
  $ntokens = count($tokens);
  if($ntokens > 1) {
    $s = 's';
    $html .= "<p>There are $ntokens participants using this email address</p>";
    $text .= "\nThere are $ntokens participants using this email address\n";
  }

  $html .= "</div>\n";
  $html .= "<div style='margin-top:1em; margin-left:2em;'>\n";

  foreach($tokens as $userid=>$token)
  {
    if($user = User::lookup($userid)) {
      $fullname = $user->fullname();
      $userid   = $user->userid();
      $token    = $token;

      $html .= "<div style='margin:15px 0;'>";
      $html .= "<div><b>$fullname</b></div>";
      $html .= "<div style='margin-left:8px;'>Userid: <b>$userid</b></div>";
      $html .= "<div style='margin-left:8px;'>Reset Token: <b>$token</b></div>";
      $html .= "</div>";

      $text .= "\n";
      $text .= "   $fullname:\n\n";
      $text .= "           Userid: $userid\n";
      $text .= "      Reset Token: $token\n";
    }
  }

  $url = full_app_uri("p=pwreset");
  $html .= "<div><p>";
  $html .= "Click <a href='$url'>here</a> to continue with login recovery";
  $html .= "</p></div>";

  $text .= "\nTo continue with login recovery, go to $url\n";

  $timeout = intval(round( PWRESET_TIMEOUT/ 60));

  $html .= "</div>";
  $html .= "<div style='margin:20px 1em;'>";
  $html .= "<p>The reset token$s will expire in $timeout minutes</p>";
  $html .= "</div>";
  $html .= html_contacts();

  $text .= "\n";
  $text .= "The reset token$s will expire in $timeout minutes\n";
  $text .= text_contacts();

  return sendmail($email, "Login Recovery", $text, $html);
}

function html_contacts()
{
  todo("add html_contacts()");
  return <<<HTMLCONTACTS
  <br>
  <div style='font-style:italic;'>TODO: Add contact info</div>
  HTMLCONTACTS;
}

function text_contacts()
{
  todo("add text_contacts()");
  return <<<CONTACTS

  --------------------
  TODO: Add contact info
  CONTACTS;
}
