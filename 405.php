<?php
namespace tlc\tts;

define('APP_DIR', dirname(__file__));

require_once(APP_DIR.'/include/init.php');
require_once(app_file('include/const.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('common/page_elements.php'));

$url = dirname($_SERVER['SCRIPT_NAME']);
$img = "$url/img/405.png";


start_page('400');
navbar();

?>

<div style='width:80%; max-width:600px; margin-top:5%; margin-left:auto; margin-right:auto;'>
  <a href='<?=$url?>'>
    <img src='<?=$img?>' alt='Click here to return to the survey' style='width:100%;'>
  </a>
</div>

<?php

print("<pre>");
print_r($url);
print("</pre><pre>");
print_r($img);
print("</pre>");

end_page();
