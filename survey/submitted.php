<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));
require_once(app_file('include/responses.php'));

function show_submitted_page($userid,$timestamp,$email_sent_to=null)
{
  $action = app_uri();
  $nonce = gen_nonce('submitted');
  $timestamp = recent_timestamp_string($timestamp);

  echo "<form id='submitted' class='submitted-box' method='post' action='$action'>";
  echo "<input type='hidden' name='nonce' value='$nonce'>";
  echo "<input type='hidden' name='submitted' value='1'>";
  echo "<div class='message'>Your survey was successfully submitted</div>";
  echo "<div class='timestamp'>Recieved $timestamp</div>";
  if($email_sent_to) {
    echo "<div class='email'>A summary was sent to $email_sent_to</div>";
  }
  else {
    $user = User::from_userid($userid);
    $email = $user->email();
    if($email) {
      echo "<div class='email'>";
      echo "<input type='hidden' name='email' value='$email'>";
      echo "<button type='submit' class='linkbutton' name='action' value='sendemail'>";
      echo "Send summary to $email";
      echo "</button>";
      echo "</div>";
    }
  } 
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
}

function send_confirmation_email($userid,$email,$content,$responses)
{
  log_dev("send_email_confirmation to $email");
  show_submitted_page($userid,$submitted['timestamp'],$email);
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

