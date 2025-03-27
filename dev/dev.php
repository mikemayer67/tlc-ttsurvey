<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/login.php'));

$cookies = resume_survey_as('kitkat15','1234567890');

echo "<h1>DEV</h1>";

MySQLExecute("delete from tlc_tt_userids where userid like 'newtest%'");
create_new_user("newtest123","Just a Test Subject",'1qaz@WSX3edcRFV','mikemayer67@vmwishes.com');
create_new_user("newtest124","Just a Test Subject",'1qaz@WSX3edcRFV');

function dump($k,$v) {
  echo "<pre>$k: $v</pre>";
}

$u = User::from_userid('newtest123');

$a = $u->get_anonid();
dump("anonid",$a);

$a = $u->get_or_create_anonid();
dump("anonid",$a);

$a = $u->get_anonid();
dump("anonid",$a);

echo "<h2>GET</h2>";
echo "<PRE>", print_r($_GET,true), "</pre>";
echo "<h2>POST</h2>";
echo "<PRE>", print_r($_POST,true), "</pre>";
echo "<h2>REQUEST</h2>";
echo "<PRE>", print_r($_REQUEST,true), "</pre>";

echo "<h2>Users</h2>";
log_dev("--User Lookup--");
$users = User::lookup('mikemayer67@vmwishes.com');
echo "<pre>" . print_r($users,true) . "</pre>";
$user = User::lookup('kitkat15');
echo "<pre>" . print_r($user,true) . "</pre>";
$user = User::lookup('snickers');
echo "<pre>" . print_r($user,true) . "</pre>";
$users = User::lookup('mikemayer67@vmwishes.com');
echo "<pre>" . print_r($users,true) . "</pre>";

echo "<h2>PHPMailer</h2>";

require_once(app_file('vendor/autoload.php'));

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->isSMTP();
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Host       = 'smtp.gmail.com';
    $mail->Port       = 465;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth   = true;
    $mail->Username   = 'trinityelcawebapps@gmail.com';
    $mail->Password   = 'gcut slja qisd jvov';
    $mail->setFrom('ttsurvey@trinityelca.org','Trinity Time and Talent Survey');
    $mail->addAddress('mikemayer67@vmwishes.com','Myself');
    $mail->Subject = 'PHPMailer GMAIL smtp test';
    $mail->isHTML(true);
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
