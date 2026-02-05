<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('survey/elements.php'));

start_nosurvey_page();

echo "<div class='ttt-splash'>";
add_img_tag('coming_soon.png','','Coming Soon');

echo "<form id='survey'>";
add_hidden_input('ajaxuri',app_uri());

echo "<div class='ttt-caption'>";
echo "There is no active survey at this time";
echo "</div>";

echo "</form>";

$user_menu = js_uri('user_menu','survey');
echo "<script type='module' src='$user_menu'></script>";

end_page();
die();
