<?php
namespace tlc\tts;

if(!defined('APP_DIR')) {define('APP_DIR', dirname(__file__));}

require_once(APP_DIR.'/include/init.php');
require_once(app_file('include/elements.php'));
require_once(app_file('include/logger.php'));

$url = dirname($_SERVER['SCRIPT_NAME']);

start_fault_page('405');

echo "<div class='ttt-splash'>";
add_link_tag(app_uri(),img_tag('405.png','','Click here to return to the survey'));
echo "</div>";

end_page();
