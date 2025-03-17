<?php
namespace tlc\tts;

if(!defined('APP_DIR')) {define('APP_DIR', dirname(__file__));}

require_once(APP_DIR.'/include/init.php');
require_once(app_file('include/page_elements.php'));

require_once(app_file('include/logger.php'));

$url = dirname($_SERVER['SCRIPT_NAME']);

start_page('405');

print("<div class='ttt-splash'>");
print(link_tag('tt.php',img_tag('405.png','','Click here to return to the survey')));
print("</div>");

end_page();
