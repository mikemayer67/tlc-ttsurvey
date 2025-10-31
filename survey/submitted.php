<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));
require_once(app_file('include/responses.php'));

function show_submitted_page($userid,$timestamp,$email_sent_to=null)
{
  $action = app_uri();
  $nonce = gen_nonce('survey-form');
  $timestamp = recent_timestamp_string($timestamp);

  $user = User::from_userid($userid);
  $email = $user->email();

  $queued_email = $_SESSION['queued-confirmation-email'] ?? false;
  if($queued_email && !$email) {
    $queued_email = false;
    $_SESSION['queued-confirmation-email'] = false;
  }

  echo "<form id='submitted' class='submitted-box' method='post' action='$action'>";
  add_hidden_input('nonce',$nonce);
  add_hidden_input('submitted',1);
  if($queued_email) {
    add_hidden_input('userid',$userid);
    add_hidden_input('ajaxuri',app_uri());
  }
  echo "<div class='message'>Your survey was successfully submitted</div>";
  echo "<div class='timestamp'>Recieved $timestamp</div>";
  echo "<div class='email'>";
  if($email_sent_to) {
    echo "A confirmation email was sent to $email_sent_to";
  }
  elseif($queued_email) {
    echo "A confirmation email will be sent to $email";
  }
  elseif($email) {
    echo "<input type='hidden' name='email' value='$email'>";
    echo "<button type='submit' class='linkbutton' name='action' value='sendemail'>";
    echo "Send summary to $email";
    echo "</button>";
  } 
  else {
    echo "Cannot send confirmation email as no address was provided";
  }
  echo "</div>";
  echo "<div class='reopen'>";
  echo "<button type='submit' class='linkbutton' name='action' value='reopen'>";
  echo "I would like to review my responses and possibly make some updates";
  echo "</button>";
  echo "</div>";

  echo "<div class='withdraw'>";
  echo "<button type='submit' class='linkbutton' name='action' value='withdraw'>";
  echo "I would like to withdraw it to make some updates";
  echo "</button>";
  echo "</div>";

  echo "<div class='restart'>";
  echo "<button type='submit' class='linkbutton' name='action' value='restart'>";
  echo "I would like to withdraw it and start over";
  echo "</button>";
  echo "</div>";

  echo "</form>";

  if($queued_email) {
    $submitted = js_uri('submitted','survey');
    echo "<script type='module' src='$submitted'></script>";
  }
}

function send_confirmation_email($userid,$email,$content,$submitted)
{
  todo("send the confirmaiton email");
  $_SESSION['queued-confirmation-email'] = false;
}

function withdraw_responses($userid,$survey_id,$restart=false)
{
  if($restart) {
    $result = restart_user_responses($userid,$survey_id);
  } else {
    $result = withdraw_user_responses($userid,$survey_id);
  }
  echo "<div class='submitted-box'>";
  if($result) {
    echo "<div class='message'>Your responses have been withdrawn.</div>";
    insert_withdraw_redirect();
  } else {
    $uri = app_uri();
    $contact = admin_contact();
    echo "<div class='error'>Something went wrong.</div>";
    echo "<div class='contact'>Please contact $contact for help.</div>";
    echo "<div class='return'><a href='$uri'>return to survey</a></div>";
  }
  echo "</div>";
}

function withdraw_and_restart($userid,$survey_id)
{
  withdraw_responses($userid,$survey_id,true);
}

function insert_withdraw_redirect()
{
  // We are going to handle this differently depending on whether or not Javascript is enabled.
  //
  //   The proper place to put the redirect is in <head>, but we're already populating the <body>
  //     when this function is called... too late to add to the <head>.
  //
  //   Javascript will be used to insert the redirect in the header after the fact.
  //
  //   For non-JS users, we'll put the redirect in the <body> and rely on long established precident
  //     that browsers honor a late redirect.

  $redirect_uri = app_uri();
  $delay = 3;
  $redirect = "<meta http-equiv='refresh' content='$delay;url=$redirect_uri'>";
  echo <<<REDIRECT
    <div class='timestamp'>Just a second while we reset things...</div>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        document.head.insertAdjacentHTML('beforeend',`$redirect`);
        });
    </script>
    <noscript>$redirect</noscript>
  REDIRECT;
}

