<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/login.php'));

$cookies = resume_survey_as('kitkat15','1234567890');
log_dev("Cookies: ".print_r($cookies,true));

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
