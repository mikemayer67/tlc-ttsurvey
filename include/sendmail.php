<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once(app_file('vendor/autoload.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/settings.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/roles.php'));

class SendmailFailure extends \Exception {}

$SendmailLogToken = '';

/**
 * Sends email to specified email address or addresses
 * @param string|[string] $email 
 * @param string $subject 
 * @param string $text 
 * @param null|string $html optional HTML version of the email body
 * @return true|false 
 */
function sendmail(mixed $email,string $subject,string $text,?string $html=null)
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

    $addresses = is_array($email) ? $email : [$email];
    foreach($addresses as $address) {
      $mail->addAddress($address);
    }
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
    $smtp_logging = ob_get_contents();
    if($smtp_logging) { 
      log_dev("SMTP logging: $smtp_logging");
    }
    ob_end_clean();
  }
}

/**
 * Sends a profile change notification to the specified user
 * @param string $email 
 * @param string $userid 
 * @param [string] $changes 
 * @return true|false 
 */
function sendmail_profile(string $email,string $userid,array $changes)
{
  $message = [];

  $message[] = [
    'type'=>'header', 
    'text'=>"A change has been made to the profile associated with userid: $userid",
  ];

  foreach($changes as $k=>$v) {
    if(is_string($k)) {
      if($v[0] !== $v[1]) {
        $message[] = [ 'type'=>'change', 'key'=>$k, 'old'=>$v[0], 'new'=>$v[1] ];
      }
    } else {
        $message[] = [ 'type'=>'change', 'key'=>$v ];
    }
  }

  $message[] = [
    'type'=>'footer',
    'lines'=>[
      'If you did not make this change, please contact one of the following:',
      '<<contacts>>',
    ],
  ];

  $text = render_text_message($message);
  $html = render_html_message($message);

  return sendmail($email, "Profile Update", $text, $html);
}

/**
 * Sends password recovery information to specified user
 * @param string $email 
 * @param [string] $tokens 
 * @param null|string &$error set to reason on failure to send email
 * @return false|true 
 */
function sendmail_recovery(string $email,array $tokens,?string &$error=null)
{
  $error = '';

  $url = full_app_uri("p=pwreset");
  $timeout = pwreset_timeout();

  $users = [];
  foreach($tokens as $userid=>$token)
  {
    if($user = User::lookup($userid)) {
      $fullname = $user->fullname();
      $userid   = $user->userid();
      $token    = $token;
      $users[]  = [$token,$fullname,$userid];
    }
  }

  $nusers = count($users);
  $s      = $nusers > 1 ? 's' : '';

  if($nusers === 0 ) {
    $error = 'No user information found for any of the recovery tokens';
    return false;
  }

  $message = [];

  $message[] = [
    'type'=>'header',
    'text'=>'Here is the login recovery information you requested:',
  ];

  if($nusers > 1) {
    $message[] = [
      'type' => 'text',
      'text' => "There are $nusers userids associated with this email address",
    ];
  }
  $message[] = ['type'=>'users','users'=>$users];

  $message[] = [
    'type'=>'footer',
    'lines'=>[
      "The reset token$s will expire in $timeout minutes.",
      "The reset token$s will expire after the first recovery attempt.",
      '<<br>>',
      "If you've closed the password reset window, you can get back to it at [[$url]]",
      '<<contacts>>',
    ],
  ];

  $text = render_text_message($message);
  $html = render_html_message($message);

  return sendmail($email, "Profile Update", $text, $html);
}

/**
 * Sends a response submission confirmation notice to the specified user
 * @param string $email 
 * @param string $userid 
 * @param string $summary 
 * @param null|string &$error set to error reason on failure to send email
 * @return false|true 
 */
function sendmail_confirmation(string $email,string $userid,string $summary, ?string &$error=null)
{
  $error = '';

  $user = User::from_userid($userid);
  if(!$user) {
    $error = 'No user information found for userid $userid';
    return false;
  }

  $name = $user->fullname();
  $message = [];

  $message[] = [
    'type'=>'header',
    'text'=>"Survey responses have been received for $name.",
  ];

  $url = full_app_uri();
  $message[] = [
    'type'=>'footer',
    'lines'=>[
      "If you would like to review or make changes, you can go back to the survey at [[$url]]",
      '<<contacts>>',
    ],
  ];

  $text = render_text_message($message);
  $html = render_html_message($message);

  return sendmail($email, 'Survey Responses Received', $text, $html);
}

/**
 * Sends an unstarted survey notice to specified user
 * @param string $email 
 * @param string $userid 
 * @param string $name 
 * @return true|false 
 */
function sendmail_no_response(string $email,string $userid,string $name)
{
  $message = [];

  $message[] = [
    'type' => 'text',
    'text' => "It appears the survey for $name has not been started",
  ];

  $message[] = [
    'type' => 'options',
    'options' => [
      'Be sure to hit the "Submit Responses" when you are done to actually submit your responses.',
      'If your are not ready to submit your responses, you can hit the "Save Draft" to come back to it later.',
    ],
  ];

  $url = full_app_uri();
  $message[] = [
    'type' => 'footer',
    'lines' => [
      "You can return to the survey at [[$url]]",
      "The userid for $name is $userid",
      '<<contacts>>',
    ],
  ];

  $text = render_text_message($message);
  $html = render_html_message($message);

  return sendmail($email, 'Survey Reminder', $text, $html);
}

/**
 * Sends an unsubmitted draft notice to specified user
 * @param string $email 
 * @param string $userid 
 * @param string $name 
 * @return true|false 
 */
function sendmail_draft_only(string $email,string $userid,string $name)
{
  $message = [];

  $message[] = [
    'type' => 'text',
    'text' => "It appears that $name has unsubmitted draft responses to the survey.",
  ];

  $message[] = [
    'type' => 'options',
    'options' => [
      'You can continue to make edits to your draft and update it using the "Save Draft" button.',
      'Once you are happy with your responses, be sure to hit the "Submit Responses" button.',
    ],
  ];

  $url = full_app_uri();
  $message[] = [
    'type' => 'footer',
    'lines' => [
      "You can return to the survey at [[$url]]",
      "The userid for $name is $userid",
      '<<contacts>>',
    ],
  ];

  $text = render_text_message($message);
  $html = render_html_message($message);

  return sendmail($email, 'Survey Reminder - unsubmitted draft', $text, $html);
}

/**
 * Sends an unsubmitted response updates notice to specified user
 * @param string $email 
 * @param string $userid 
 * @param string $name 
 * @return true|false 
 */
function sendmail_unsubmitted_updates(string $email,string $userid, string $name)
{
  $message = [];

  $message[] = [
    'type' => 'text',
    'text' => "It appears that $name has unsubmitted updates to the survey responses.",
  ];
  $message[] = [
    'type' => 'options',
    'options' => [
      'You can continue to make edits to your draft and update it using the "Save Draft" button.',
      'When you are happy with your updated responses, be sure to hit the "Submit Responses" button.',
    ],
  ];
  $message[] = [
    'type' => 'text',
    'text' => 'If you decide you are happy with your previoulsy submitted respones, you can hit the "Delete Draft" button to stop receiving these reminder emails.',
  ];


  $url = full_app_uri();
  $message[] = [
    'type' => 'footer',
    'lines' => [
      "You can return to the survey at [[$url]]",
      "The userid for $name is $userid",
      '<<contacts>>',
    ],
  ];

  $text = render_text_message($message);
  $html = render_html_message($message);

  return sendmail($email, 'Survey Reminder - unsubmitted updates', $text, $html);
}

/**
 * Sends a bug report to the system admins
 * @param string $errid error key used to identify the error in the app log
 * @param null|string $errmsg message written into the app log
 * @param null|string $usermsg message submitte by reporter
 * @param null|string $reporter userid of the person that submitted the bug report
 * @param null|string $issue_url Github issue
 * @return void 
 */
function sendmail_bug_report(
  string $errid, 
  ?string $errmsg=null, 
  ?string $usermsg=null, 
  ?string $reporter=null, 
  ?string $issue_url=null,
  )
{
  $email = [admin_email()];
  
  $techs = tech_admins();
  foreach($techs as $tech_userid) {
    $tech = User::from_userid($tech_userid);
    $tech_email = $tech->email();
    if(!in_array($tech_email,$email)) { $email[] = $tech_email; }
  }

  if($reporter) {
    $user = User::from_userid($reporter);
    if($user) { $reporter = $user->fullname(); }
  }
  if(!$reporter) {
    $reporter = "(anonymous)";
  }

  $message = [];
  $message[] = [ 'type' => 'key-value', 'key' => 'Reported by', 'value' => $reporter ];

  if($errmsg) {
    $message[] = ['type' => 'key-value', 'key' => "Log entry [$errid]", 'value' => $errmsg];
  } else {
    $message[] = ['type' => 'key-value', 'key' => "Log entry", 'value' => $errid];
  }

  $message[] = ['type' => 'key-value', 'key' => "User provided info", 'value' => ($usermsg ?? '(none)')];

  if($issue_url) {
    $message[] = ['type' => 'key-value', 'key' => 'Github issue', 'value' => $issue_url];
  }

  $text = render_text_message($message);
  $html = render_html_message($message);

  return sendmail($email, "Survey Bug Reported ($errid)", $text, $html);
}

//------------------------------------------------------------------------------
// Email rendering engines
//------------------------------------------------------------------------------

function render_text_message($message)
{
  $rval = '';
  foreach($message as $e) {
    switch($e['type']) {
    case 'header':
      $text = parse_text_string($e['text']);
      $rval .= "$text\n\n";
      break;
    case 'text':
      $text = parse_text_string($e['text']);
      $rval .= "$text\n";
      break;
    case 'key-value':
      $key = $e['key'];
      $value = parse_text_string(preg_replace('/\s+/',' ', trim($e['value'])));
      $sep = ' ';
      if(strlen($value) > 75) {
          $sep = "\n    ";
          $value = wordwrap($value, 80, $sep);
      }
      $rval .= "$key:$sep$value\n\n";
      break;
    case 'change':
      $key = $e['key'];
      $old = $e['old'] ?? '(undisclosed)';
      $new = $e['new'] ?? '(undisclosed)';
      $rval .= "  Old $key: $old\n  New $key: $new\n\n";
      break;
    case 'options':
      foreach($e['options'] as $option) {
        $rval . "  - $option\n";
      }
      $rval .= "\n";
      break;
    case 'users':
      foreach ($e['users'] as [$token,$fullname,$userid]) {
        $rval .= "\n";
        $rval .= "   $fullname:\n";
        $rval .= "      Userid: $userid\n";
        $rval .= "       Token: $token\n";
      }
      break;
    case 'footer':
      $rval .= "\n";
      foreach($e['lines'] as $line)
      {
        if($line==='<<contacts>>') {
          $rval .= "\n";
          if($contacts = admin_contacts()) { 
            $rval .= "    For general help with the survey, contact:\n";
            $rval .= render_text_contacts($contacts);
            $rval .= "\n";
          }
          if($contacts = admin_contacts('content')) {
            $rval .= "    To report an issue with the survey content, contact:\n";
            $rval .= render_text_contacts($contacts);
            $rval .= "\n";
          }
          if($contacts = admin_contacts('tech')) {
            $rval .= "    To report an issue with the survey functionality, contact:\n";
            $rval .= render_text_contacts($contacts);
            $rval .= "\n";
          }
        } 
        else {
          $line = parse_text_string($line);
          $rval .= "$line\n";
        }
      }
      break;
    }
  }
  return $rval;
}

function render_html_message($message)
{
  $rval = "<div style='margin-left:1em;'>\n";
  foreach($message as $e) {
    switch($e['type']) {
    case 'header':
      $text = parse_html_string($e['text']);
      $rval .= "<p style='font-weight:bolder; margin:1em 0;'>$text</p>\n";
      break;
    case 'text':
      $text = parse_html_string($e['text']);
      $rval .= "<p>$text</p>\n";
      break;
    case 'key-value':
      $key = $e['key'];
      $value = parse_html_string(preg_replace('/\s+/',' ', trim($e['value'])));
      $rval .= "<p style='font-weight:bolder; margin:1em 0 0 0;'>$key</p>\n";
      $rval .= "<p style='font-weight:normal; margin:0 0 0 2em;'>$value</p>\n";
      break;
    case 'change':
      $key = $e['key'];
      $old = $e['old'] ?? '(undisclosed)';
      $new = $e['new'] ?? '(undisclosed)';
      $rval .= "<ul><li>Old $key: $old</li><li>New $key: $new</li></ul>\n";
      break;
    case 'options':
      $rval .= "<ul>";
      foreach($e['options'] as $option) {
        $rval .= "<li>$option</li>";
      }
      $rval .= "</ul>";
      break;
    case 'users':
      foreach ($e['users'] as [$token,$fullname,$userid]) {
        $rval .= "<div style='margin:1em 0;'>\n";
        $rval .= "<div><b>$fullname</b></div>\n";
        $rval .= "<div style='margin-left:0.5em;'>Userid: <b>$userid</b></div>\n";
        $rval .= "<div style='margin-left:0.5em;'>Token: <b>$token</b></div>\n";
        $rval .= "</div>\n";
      }
      break;
    case 'footer':
      $rval .= '<br>';
      foreach($e['lines'] as $line)
      {
        if($line==='<<contacts>>') {
          $rval .= "<div style='margin:1em;font-style:italic'>\n";
          if($contacts = admin_contacts()) { 
            $contacts  = render_html_contacts($contacts);
            $rval .= "<div>For general help with the survey, contact $contacts</div>\n";
          }
          if($contacts = admin_contacts('content')) {
            $contacts = render_html_contacts($contacts);
            $rval .= "<div>To report an issue with the survey content, contact: $contacts</div>\n";
          }
          if($contacts = admin_contacts('tech')) {
            $contacts = render_html_contacts($contacts);
            $rval .= "<div>To report an issue with the survey functionality, contact: $contacts</div>\n";
          }
          $rval .= "</div>\n";
        } 
        else {
          $line = parse_html_string($line);
          $rval .= "<div>$line</div>\n";
        }
      }
      break;
    }
  }
  $rval .= "</div>\n";
  return $rval;
}

function parse_text_string($text)
{
  $text = str_replace('<<br>>',"",$text);
  $text = preg_replace('/\[\[(.*?)\]\]/','$1',$text);
  return $text;
}

function parse_html_string($text)
{
  $text = str_replace('<<br>>',"<div style='margin-top:1em;'></div>",$text);
  $text = preg_replace('/\[\[(.*?)\]\]/','<a href="$1">$1</a>',$text);
  return $text;
}

function render_text_contacts($contacts)
{
  $rval = '';
  foreach($contacts as $contact) {
    $name  = $contact['name']  ?? null;
    $email = $contact['email'] ?? null;
    if($email) { $rval .= "       $name ($email)\n"; } 
    else       { $rval .= "       $name\n";          }
  }
  return $rval;
}

function render_html_contacts($contacts)
{
  $links = [];

  foreach($contacts as $contact) {
    $name  = $contact['name']  ?? null;
    $email = $contact['email'] ?? null;

    if($email) {
      $subject = "Help needed with ".app_name();
      $links[] = "<a href='mailto:$name<$email>?subject=$subject'>$name</a>";
    } else {
      $links[] = $name;
    }
  }

  $nlinks = count($links);
  if($nlinks === 0) { return ''; }
  if($nlinks === 1) { return $links[0]; }
  if($nlinks === 2) { return implode(' or ',$links); }

  $last = array_pop($links);
  return implode(', ',$links) . ", or $last";
}


