<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/users.php'));

print("<h1>DEV</h1>");

MySQLExecute("delete from tlc_tt_userids where userid like 'newtest%'");
create_new_user("newtest123","Just a Test Subject",'1qaz@WSX3edcRFV','mikemayer67@vmwishes.com');
create_new_user("newtest124","Just a Test Subject",'1qaz@WSX3edcRFV');

function dump($k,$v) {
  print("<pre>$k: $v</pre>");
}

$u = User::from_userid('newtest123');
$a = $u->anonid();
dump("anonid",$a);
$a = $u->anonid();
dump("anonid",$a);

