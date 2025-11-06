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

$userid = active_userid();

start_preview_page($survey_title,$userid,$preview_js);

render_survey('preview',$content);

$user_menu = js_uri('user_menu','survey');
echo "<script>const ttt_preview = true;</script>";
echo "<script type='module' src='$user_menu'></script>";

end_page();
die();
