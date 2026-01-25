<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/sendmail.php'));
require_once(app_file('include/settings.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-participants');

$survey_id = active_survey_id();
if(!$survey_id) {
  send_ajax_internal_error("No active survey... should not have called this function");
}

start_ob_logging();

try {
  $userids = $_POST['userids'];
  if(!$userids)           { 
    send_ajax_bad_request('No userids specified');     }
  if(!is_array($userids)) { send_ajax_bad_request('Userids must be an array'); }
}
catch(\Throwable $e)
{
  send_ajax_bad_request($e->getMessage());
}

$user_status = array_fill_keys( $userids, ['draft'=>null, 'submitted'=>null] );

$qmarks = '?' . str_repeat(',?',count($userids)-1);
$types  = str_repeat('s',count($userids));
$query = <<<SQL
  SELECT userid,
         UNIX_TIMESTAMP(draft)     as draft,
         UNIX_TIMESTAMP(submitted) as submitted
    FROM tlc_tt_user_status
   WHERE userid in ($qmarks) and survey_id=$survey_id
  SQL;

$rows = MySQLSelectRows($query,$types,...$userids);
foreach($rows as $row)
{
  $userid    = $row['userid'];
  $draft     = $row['draft'];
  $submitted = $row['submitted'];
  $user_status[$userid] = ['draft'=>$draft, 'submitted'=>$submitted];
}

$now = time();
$reminder_freq = reminder_freq() * 3600;

$email_status = array(
  'sent'=>[],
  'no_user'=>[],
  'no_email'=>[],
  'not_needed'=>[],
  'too_soon'=>[],
  'failed'=>[],
);

foreach($user_status as $userid=>$info)
{
  $user = User::from_userid($userid);
  if(!$user) { 
    $email_status['no_user'][] = $userid;
    continue;
  }
  $processed = [];

  $email = $user->email();
  if(!$email) {
    $email_status['no_email'][] = $userid;
    continue;
  }
  if($info['draft']) { 
    if($info['submitted']) { $subject = 'unsbumitted-updates'; }
    else                   { $subject = 'draft-only';          }
  } else {
    if($info['submitted']) { $subject = null;          }
    else                   { $subject = 'no-response'; }
  }

  if(is_null($subject)) {
    $email_status['not_needed'][] = $userid;
    continue;
  }

  $query = <<<SQL
    SELECT subject, UNIX_TIMESTAMP(last_sent) as last_sent, email
      FROM tlc_tt_reminder_emails
     WHERE userid=?
    SQL;

  $hist = MySQLSelectRow($query,'s',$userid);
  log_dev("$userid history: ".print_r($hist,true));

  if( $hist && $hist['subject'] === $subject ) {
    $last_sent = $hist['last_sent'];
    log_dev("last_sent:$last_sent, now=$now, freq=$reminder_freq");
    if($now < $last_sent + $reminder_freq) {
      $email_status['too_soon'][] = $userid;
      continue;
    }
  }

  $name = $user->fullname();

  switch($subject) {
  case 'unsbumitted-updates':
    $result = sendmail_unsubmitted_updates($email,$userid,$name);
    break;
  case 'draft-only':
    $result = sendmail_draft_only($email,$userid,$name);
    break;
  case 'no-response':
    $result = sendmail_no_response($email,$userid,$name);
    break;
  default:
    internal_error("Should not see subject=$subject");
    break;
  }
  if($result) 
  { 
    $email_status['sent'][]   = $userid;

    $query = <<<SQL
      INSERT into tlc_tt_reminder_emails (userid, subject, last_sent, email)
      VALUES (?,?,CURRENT_TIMESTAMP,?)
          ON DUPLICATE KEY update subject=?, last_sent=CURRENT_TIMESTAMP, email=?
    SQL; 

    $values = [$userid, $subject, $email, $subject, $email];

    MySQLExecute($query,'sssss',...$values);
  }
  else
  { 
    $email_status['failed'][] = $userid; 
  }
}

end_ob_logging();

$response = new AjaxResponse();
foreach($email_status as $k=>$v) { $response->add($k,$v); }
$response->send();

die();
