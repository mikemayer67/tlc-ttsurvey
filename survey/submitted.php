<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));
require_once(app_file('include/responses.php'));
require_once(app_file('include/sendmail.php'));

function show_submitted_page(string $userid,int $survey_id,int $timestamp)
{
  // We can get to this function by a few different means and in various states.
  //   Let's take a look at them and also note what should happen in javascript (if anything).
  //
  // Email status states:
  //   - queued: A confirmation email needs to be sent, but hasn't been sent yet
  //   -   sent: A conirmaation email has been sent
  //   -   link: No confirmation email has been sent, a link is provided for user to request it
  //   -   none: There is no email address on record for the user, not confirmation email can be sent
  //
  // Case 1: User came back to (or reloaded) tt.php when there is a submitted but no draft.
  //     1a: queued
  //         - we should only see this if something went screwy... but possible
  //         - js: send confirmation via ajax and change status to sent
  //         - no-js: n/a - only queued when js is enabled. (not handling the edge case where user
  //                 disabled js in the very short period between submitting the form and loading this page)
  //
  //     1b: sent
  //         - js: no need to do anything, changing user email has no effect on this status
  //
  //     1c: link
  //         - add the <a> link to request a new confirmation email
  //         - no-js: redirects to the page that sends the email and then redirects back here
  //         - js: things get interesting here
  //           - if user removes their email address, jump to case 1d
  //           - if user clicks on link to send email, send via ajax and jump to case 1b
  //     
  //     1d: none
  //         - email status should be set to none
  //         - no-js: n/a (adding email will require manual reload to take effect)
  //         - js: if user adds an email address, jump to case 1c
  //
  // Case 2: User just submitted new responses
  //   - 2a: no email address associated with user
  //         - email status is set to none
  //         - proceed as in Case 1
  //   - 2b: email address exists for user
  //         - no-js: confirmation email should have been sent prior to this function being 
  //                  called, status should be set to sent
  //         - js: status should have been sent to queued
  //         - proceed to Case 1
  
  $action = app_uri();
  $timestamp = recent_timestamp_string($timestamp);

  $user       = User::from_userid($userid);
  $email      = $user->email();
  $email_sent = confirmation_email_sent($userid,$survey_id);

  $queued_email = $_SESSION['queued-confirmation-email'] ?? false;
  if($queued_email && !$email) {
    $queued_email = false;
    $_SESSION['queued-confirmation-email'] = false;
  }

  echo "<div class='submitted-wrapper'>";
  echo "<form id='submitted' class='submitted-box' method='post' action='$action'>";
  add_hidden_input('submitted',1);
  add_hidden_input('userid',$userid);

  echo "<div class='message'>Your survey was successfully submitted</div>";
  echo "<div class='timestamp'>Recieved $timestamp</div>";
  echo "<div class='email'>";

  // See discussion on confirmation email status above
  if($queued_email) 
  {
    $needs_js = true;
    add_hidden_input('email-status','queued');
    add_hidden_input('ajaxuri',app_uri());
    echo "A confirmation email will be sent to $email shortly";
  }
  elseif($email_sent) 
  {
    $needs_js = false;
    add_hidden_input('email-status','sent');
    $email     = $email_sent['address'];
    $timestamp = $email_sent['timestamp'];
    $now       = time();
    if($now > $timestamp + 300) {
      // only add timestamp if it's been over 5 minutes
      $timestamp = ' at ' . recent_timestamp_string($timestamp);
    } else {
      $timestamp = '';
    }
    echo "A confirmation email was sent to $email$timestamp";
  }
  elseif($email) 
  {
    $needs_js = true;
    add_hidden_input('email-status','link');
    echo "<button type='submit' class='linkbutton' name='action' value='sendemail'>";
    echo "Send summary to $email";
    echo "</button>";
  } 
  else 
  {
    $needs_js = true;
    add_hidden_input('email-status','none');
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
  echo "</div>";  // .submitted-wrapper

  if($needs_js) {
    $submitted = js_uri('submitted','survey');
    echo "<script type='module' src='$submitted'></script>";
  }
}

function send_confirmation_email($userid,$survey_id,$email,$content,$submitted)
{
  todo("Add summary info to confirmation email");
  $error = '';
  sendmail_confirmation($email,$userid,'',$error);
  if(empty($error)) {
    confirmation_email_sent($userid,$survey_id,$email);
    $_SESSION['queued-confirmation-email'] = false;
  }
}

function withdraw_responses($userid,$survey_id,$restart=false)
{
  if($restart) {
    $result = restart_user_responses($userid,$survey_id);
  } else {
    $result = withdraw_user_responses($userid,$survey_id);
  }
  echo "<div class='submitted-wrapper'><div class='submitted-box'>";
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
  echo "</div></div>";
}

function withdraw_and_restart($userid,$survey_id)
{
  withdraw_responses($userid,$survey_id,true);
}

/**
 * Renders the submission failure due to conflicting submissions
 * @param string $userid 
 * @param string $action 
 * @param array $change result from compare_status_timestamps
 * @return void 
 */
function fail_conflicting_submit(string $userid,string $action,array $change)
{
   // Unlike the otehr rendering support functions in this file,
   //   this one is called before start_survey_page, and must therefore
   //   call this explicitly.

  $navbar_args = [
    'title'     => active_survey_title(),
    'userid'    => $userid,
    'draft'     => $change['cur_draft'],
    'submitted' => $change['cur_submitted'],
  ];

  switch ($action) {
    case 'delete':
      $message = 'Failed to delete the draft.';
      break;
    case 'save':
      $message = 'Failed to save your draft changes.';
      break;
    case 'submit':
      $message = 'Failed to submit your responses.';
      break;
    default:
      $message = 'Failed to accept your updates.';
      break;
  }

  $modified = $change['modified'];
  $reason  = "While you were working on this form, $modified.";

  start_survey_page($navbar_args);

  $contact = admin_contact();
  $uri = app_uri();

  echo "<div class='submitted-wrapper'>";
  echo "<form id='failed' class='submitted-box' method='post'>";
  echo "<div class='error'>$message</div>";
  echo "<div class='conflict'>$reason</div>";
  echo "<br>";
  echo "<div class='contact'>Did you have another survey form open?</div>";
  echo "<div class='contact'>If not, please contact $contact for help.</div>";
  echo "<br>";
  echo "<div class='return'><a href='$uri'>Click here to reload the survey with updated data</a>.</div>";
  echo "</form>";
  echo "</div>";
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

