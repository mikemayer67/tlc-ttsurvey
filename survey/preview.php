<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

log_mark("-------------- Start of Preview --------------");

validate_and_retain_nonce('preview');

require_once(app_file('include/cookiejar.php'));
require_once(app_file('include/survey_content.php'));
require_once(app_file('survey/elements.php'));
require_once(app_file('include/elements.php'));
require_once(app_file('survey/render.php'));

$page_title = 'Survey Preview';

$survey_title = $_POST['title'] ?? '[No Name]';
$content      = SurveyContent::fromJsonString($_POST['content']??''); 
$preview_js   = filter_var($_POST['preview_js'] ?? false, FILTER_VALIDATE_BOOLEAN);

$userid = active_userid();

start_preview_page($survey_title,$userid,$preview_js);

$img=img_uri("icons8/info.png");
echo(" <!-- Preview Lock Modal -->");
echo(" <div class='preview modal' id='preview-lock-modal'>");
echo("   <div class='content'>");
echo("     <img src='$img'>");
echo("     <div class='text-box'>");
echo("       <p class='line one'></p>");
echo("       <p class='line two'></p>");
echo("     </div>");
echo("     <div class='buttons'>");
echo("       <button class='confirm'>OK</button>");
echo("     </div>");
echo("   </div>");
echo(" </div>");


render_survey('preview',$content);

$user_menu = js_uri('user_menu','survey');

$admin_nonce = get_nonce('admin');
echo "<script>const admin_nonce='$admin_nonce';</script>";
add_admin_lock();

echo "<script>const ttt_preview = true;</script>";
echo "<script type='module' src='",js_uri('preview','survey'),"'></script>";
echo "<script type='module' src='$user_menu'></script>";

end_page();
die();