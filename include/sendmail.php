<?php
namespace TLC\TTSurvey;

/**
 * Handle sending of email
 */

if( ! defined('WPINC') ) { die; }

require_once plugin_path('include/const.php');
require_once plugin_path('include/logger.php');
require_once plugin_path('include/surveys.php');
require_once plugin_path('include/markdown.php');

function sendmail_render_message($subject,$custom_content,$message_data)
{
  $subject_php = plugin_path("include/sendmail/$subject.php");
  if(!file_exists($subject_php)) {
    log_error("Attempt to render invalid sendmail subject ($subject)");
    return null;
  }

  $custom_content = render_markdown($custom_content);

  ob_start();
  require $subject_php;
  $message = ob_get_contents();
  ob_end_clean();
  return $message;
}

function _sendmail_send($email,$subject,$message_data) 
{
  $survey = current_survey();
  $content = $survey->content();

  $message_data['title'] = $survey->name();

  $message = sendmail_render_message(
    $subject,
    $content['sendmail'][$subject],
    $message_data,
  );

  return wp_mail(
    $email,
    $survey->name() . ' Time & Talent survey',
    $message,
    'Content-Type: text/html; charset=UTF-8',
  );
}

function sendmail_login_recovery($email,$keys)
{
  log_info("Send login recovery email to $email");

  return _sendmail_send( $email, 'recovery', array( 'keys'=>$keys ),
  );

}

function sendmail_welcome($email, $userid, $fullname)
{
  log_info("Send welcome email to $userid: $email");

  return _sendmail_send(
    $email, 
    'welcome', 
    array(
      'email'=>$email,
      'userid'=>$userid,
      'fullname'=>$fullname,
    ),
  );
}

function sendmail_profile($email,$userid,$changed,$from,$to)
{
  log_dev("Send profile update to $userid: $email");

  return _sendmail_send(
    $email,
    'profile',
    array(
      'userid'=>$userid,
      'changed'=>$changed,
      'from'=>($from ? $from : "(none)"),
      'to'=>($to ? $to : "(none)"),
    ),
  );
}
