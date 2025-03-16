<?php
namespace tlc\tts;

if(!defined('APP_DIR')) {define('APP_DIR', dirname(__file__));}

require_once(APP_DIR.'/include/init.php');
require_once(app_file('include/const.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/page_elements.php'));

$url = dirname($_SERVER['SCRIPT_NAME']);
$img = preg_replace('#//#','/', "$url/img/405.png");

start_page('400');
?>

<div style='width:80%; max-width:600px; margin-top:5%; margin-left:auto; margin-right:auto;'>
  <a href='<?=$url?>'>
    <img src='<?=$img?>' alt='Click here to return to the survey' style='width:100%;'>
  </a>
</div>

<?php

end_page();
