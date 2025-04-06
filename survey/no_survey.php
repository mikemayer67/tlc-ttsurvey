<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/elements.php'));

start_page('no_survey');

echo "<div class='ttt-splash'>";
add_img_tag('coming_soon.png','','Coming Soon');

echo "<div class='ttt-caption'>";
echo "There is no active survey at this time";
echo "</div>";

end_page();
