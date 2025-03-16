<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/page_elements.php'));

start_page('no_survey');

print("<div class='ttt-splash'>");
print(img_tag('coming_soon.png','','Coming Soon'));

print("<div class='ttt-caption'>");
print("There is no active survey at this time");
print("</div>");

end_page();
