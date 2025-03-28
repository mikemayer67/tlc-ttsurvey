<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/login.php'));
require_once(app_file('include/settings.php'));

function dump($k,$v) {
  echo "<pre>$k: ".print_r($v,true)."</pre>";
}

$v = get_setting('junk');
dump("v",$v);
set_setting('junk',5);
$v = get_setting('junk');
dump("v",$v);

$v = get_setting('horse');
dump("v",$v);
dump("null?",is_null($v)?'True':'False');
dump("set?",isset($v)?'True':'False');
dump("empty?",empty($v)?'True':'False');
dump("Truthy?",$v?'True':'False');

echo "<h1>resume</h1>";

$cookies = resume_survey_as('kitkat15','1234567890');

echo "<h1>Admins</h1>";
$admins = survey_admins();
dump('admins',$admins);
$admins = content_admins();
dump('content',$admins);
$admins = tech_admins();
dump('tech',$admins);

$admins = admin_contacts();
dump('admins',$admins);
$admins = admin_contacts('content');
dump('content',$admins);
$admins = admin_contacts('tech');
dump('tech',$admins);

/*
MySQLExecute("delete from tlc_tt_userids where userid like 'newtest%'");
create_new_user("newtest123","Just a Test Subject",'1qaz@WSX3edcRFV','mikemayer67@vmwishes.com');
create_new_user("newtest124","Just a Test Subject",'1qaz@WSX3edcRFV');

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
$user->set_fullname("I am a Krazy Kat");
echo "<pre>" . print_r($user,true) . "</pre>";
$users = User::lookup('mikemayer67@vmwishes.com');
echo "<pre>" . print_r($users,true) . "</pre>";

log_dev("--Set Fullname--");

User::lookup('mikemayer67')->set_fullname("Michael A. Mayer");
User::lookup('mikemayer67')->set_fullname("Mike Mayer");
User::lookup('mikemayer67')->set_fullname("Mike Mayer");
 */

