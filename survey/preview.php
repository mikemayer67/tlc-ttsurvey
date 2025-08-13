<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Preview --------------");

validate_and_retain_nonce('preview');

echo "<html><head><title>Survey Preview</title></head><body>";

require_once(app_file('include/login.php'));
$active_user = active_userid();
echo "<h2>Active Userid: $active_user</h2>";
log_dev("Active User: $active_user");

$title = $_POST['title'] ?? '[No Name]';
$content = json_decode($_POST['content'] ?? '',true);
echo "<h1>Title</h1><pre>$title</pre>";
echo "<h1>Content</h1><pre>".print_r($content,true)."</pre>";

echo "</body></html>";

die();
