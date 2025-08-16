<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Preview --------------");

validate_and_retain_nonce('preview');

require_once(app_file('include/elements.php'));
require_once(app_file('include/login.php'));

$page_title = 'Survey Preview';

$survey_title = $_POST['title'] ?? '[No Name]';
$content = json_decode($_POST['content'] ?? '',true);
$preview_js = $_POST['preview_js'] ?? false;

$active_user = active_userid();
log_dev("Active User: $active_user");

start_page('survey',[
  'survey_title'=>$survey_title,
  'js_enabled'=>$preview_js,
]);

echo "<h2>Active Userid: $active_user</h2>";

echo "<h2>Preview Javascript: $preview_js</h2>";

echo "<h1>Content</h1><pre>".print_r($content,true)."</pre>";

end_page();

die();
