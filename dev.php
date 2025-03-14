<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/users.php'));

print("<h1>DEV</h1>");

$r = MySQLExecute("delete from tlc_tt_userids where userid like 'newtest%'");
$r = create_new_user("newtest123","Just a Test Subject",'1qaz@WSX3edcRFV','mikemayer67@vmwishes.com');
$r = create_new_user("newtest124","Just a Test Subject",'1qaz@WSX3edcRFV');
$u = User::from_userid('newtest123');
print("<pre>".print_r($u,true)."</pre>");
$r = $u->set_admin(true);
$u = User::from_userid('newtest123');
print("<pre>".print_r($u,true)."</pre>");
$v = $u->validate_password("my_password");
print("<pre>validate #1: ".print_r($v,true)."</pre>");
$v = $u->validate_password("1qaz@WSX3edcRFV");
print("<pre>validate #2: ".print_r($v,true)."</pre>");
$t = $u->get_password_reset_token();
print("<pre>token:$t</pre>");
$error = '';
$u->update_password($t,'new$ password',$error);
log_dev("Error = $error");



