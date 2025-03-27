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
    log_dev("SMTP logging: ".ob_get_contents());
    ob_end_clean();

    log_dev('Message has been sent');
    return true;
  } 
  catch (Exception $e) 
  {
    log_dev("SMTP logging:\n".ob_get_contents());
    ob_end_clean();
    todo("Add sendmail failure to displayed status");
    log_dev("Failed to send email {$mail->ErrorInfo}");
    return false;
  }
}

function sendmail_profile(...$args)
{
  todo("Implement sendmail capability");
  log_dev("sendmail_profile: ".log_array($args));
}


function sendmail_recovery($email,$tokens)
{
  log_dev("sendmail_recovery($email,...)\n".print_r($tokens,true));
  $ntokens = count($tokens);

  $html  = "<div style='margin-left:1em;'>\n";
  $html .= "<p>Here is the login recovery information you requested:</p>\n";

  $text = "Here is the login recovery information you requested:\n";

  $s = '';
  $ntokens = count($tokens);
  if($ntokens > 1) {
    $s = 's';
    $html .= "<p>There are $ntokens participants using this email address</p>\n";
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

      $html .= "<div style='margin:15px 0;'>\n";
      $html .= "<div><b>$fullname</b></div>\n";
      $html .= "<div style='margin-left:8px;'>Userid: <b>$userid</b></div>\n";
      $html .= "<div style='margin-left:8px;'>Reset Token: <b>$token</b></div>\n";
      $html .= "</div>\n";

      $text .= "\n";
      $text .= "   $fullname:\n\n";
      $text .= "           Userid: $userid\n";
      $text .= "      Reset Token: $token\n";
    }
  }

  $url = full_app_uri("p=pwreset");
  $html .= "<div><p>\n";
  $html .= "Click <a href='$url'>here</a> to continue with login recovery\n";
  $html .= "</p></div>\n";

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
