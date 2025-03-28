<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once(app_file('vendor/autoload.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/settings.php'));

class SendmailFailure extends \Exception {}

$SendmailLogToken = '';

function sendmail($email,$subject,$text,$html=null)
{
  if(!$html) { $html = $text; }

  $mail = new PHPMailer(true);

  try {
    //Server settings
    $host     = smtp_host();
    $port     = smtp_port();
    $auth     = smtp_auth();
    $username = smtp_username();
    $password = smtp_password();

    if(!isset($host))     { throw new SendmailFailure('smtp_host not set in databse'); }
    if(!isset($username)) { throw new SendmailFailure('smtp_username not set in database'); }
    if(!isset($password)) { throw new SendmailFailure('smtp_password not set in database'); }

    $mail->isSMTP();
    $mail->SMTPDebug  = smtp_debug();
    $mail->Host       = $host;
    $mail->Port       = $port;
    $mail->SMTPSecure = $auth ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth   = true;
    $mail->Username   = $username;
    $mail->Password   = $password;

    $reply_email = smtp_reply_email();
    $reply_name  = smtp_reply_email();

    $mail->setFrom(
      $reply_email ?? $username,
      $reply_name ?? app_name()
    );

    if($reply_email && $reply_name) {
      $mail->addReplyTo($reply_email,$reply_name);
    } elseif($reply_email) {
      $mail->addReplyTo($reply_email);
    } elseif($reply_name) {
      $mail->addReplyTo($username,$reply_name);
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
    return true;
  } 
  catch (SendmailFailure $e)
  {
    global $SendmailLogToken;
    $SendmailLogToken = gen_token(4);
    log_error("[$SendmailLogToken] Failed to send email: ".$e->getMessage());
    return false;
  }
  catch (Exception $e) 
  {
    global $SendmailLogToken;
    $SendmailLogToken = gen_token(4);
    log_error("[$SendmailLogToken] Failed to send email {$mail->ErrorInfo}");
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

function sendmail_recovery($email,$tokens,&$error=null)
{
  $error = '';

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
      $html .= "<div style='margin-left:8px;'>Token: <b>$token</b></div>";
      $html .= "</div>";

      $text .= "\n";
      $text .= "   $fullname:\n";
      $text .= "      Userid: $userid\n";
      $text .= "       Token: $token\n";
    }
  }

  $url = full_app_uri("p=pwreset");
  $timeout = intval(round( pwreset_timeout()/ 60));

  $html .= "</div>";
  $html .= "<div style='margin:20px 1em;'>";
  $html .= "<div>The reset token$s will expire in $timeout minutes.</div>";
  $html .= "<div>The reset token$s will expire after the first recovery attempt.</div>";
  $html .= "<div style='margin-top:1em;'>";
  $html .= "If you've closed the password reset window, click <a href='$url'>here to continue</a> with login recovery.";
  $html .= "</div>";
  $html .= "</div>";
  $html .= html_contacts();

  $text .= "\n";
  $text .= "The reset token$s will expire in $timeout minutes\n";
  $text .= "The reset token$s will expire after the first recovery attempt.\n";
  $text .= "\n";
  $text .= "If you've closed the password reset window, you can get back to it at $url\n";
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
