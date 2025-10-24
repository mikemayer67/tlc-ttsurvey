<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));

function show_submitted_page($userid,$content,$responses,$email_sent_to=null)
{
  $action = app_uri();
  $nonce = gen_nonce('submitted');
  $timestamp = recent_timestamp_string($responses['timestamp']);

  echo "<form id='submitted' method='post' action='$action'>";
  echo "<input type='hidden' name='nonce' value='$nonce'>";
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
      echo "<button type='submit' class='linkbutton' name='action' value='sendemail'>";
      echo "Send summary to $email";
      echo "</button>";
      echo "</div>";
    }
  } 
  echo "<div class='reopen'>";
  echo "<button type='submit' class='linkbutton' name='action' value='reopen'>";
  echo "I would like to review it and possibly make some updates";
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

