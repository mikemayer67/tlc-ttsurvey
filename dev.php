<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));

print("<h1>DEV</h1>");

$r = MySQLSelectRows("select * from tlc_tt_userids where id>?",'i',31);
print("<pre>".print_r($r,true)."</pre>");


