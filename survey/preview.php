<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Preview --------------");

validate_and_retain_nonce('preview');

require_once(app_file('include/elements.php'));
require_once(app_file('include/login.php'));
require_once(app_file('survey/render.php'));


$page_title = 'Survey Preview';

$survey_title = $_POST['title'] ?? '[No Name]';
$content      = json_decode($_POST['content'] ?? '',true);
$preview_js   = filter_var($_POST['preview_js'] ?? false, FILTER_VALIDATE_BOOLEAN);

$active_user = active_userid();

echo "<script>const ttt_preview = true;</script>";

start_page('survey', [
  'survey_title'=>$survey_title,
  'js_enabled'=>$preview_js,
  'status'=>'Preview',
]);

render_survey($active_user,$content,['is_preview'=>true,'preview_js'=>$preview_js]);

//echo "<h1>Content</h1>";
//echo "<pre>".print_r($content,true)."</pre>";

end_page();

die();
