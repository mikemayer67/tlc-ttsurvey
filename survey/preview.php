<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Preview --------------");

validate_and_retain_nonce('preview');

require_once(app_file('include/elements.php'));
require_once(app_file('include/login.php'));

$page_title = 'Survey Preview';

$survey_title = $_POST['title'] ?? '[No Name]';
$content = json_decode($_POST['content'] ?? '',true);

$active_user = active_userid();
log_dev("Active User: $active_user");

start_page('survey');

echo "<h2>Active Userid: $active_user</h2>";

echo "<h1>Title</h1><pre>$survey_title</pre>";
echo "<h1>Content</h1><pre>".print_r($content,true)."</pre>";

end_page();

die();
