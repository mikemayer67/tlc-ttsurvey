<?php
namespace tlc\tts;

require_once(APP_DIR.'/include/init.php');
require_once(app_file('include/const.php'));
require_once(app_file('include/logger.php'));

$dir = preg_replace("/\/[^\/]+\.php$/", "", $_SERVER['SCRIPT_NAME']);
?>

<html>
  <head>
    <title>Oops...</title>
  </head>
  <body>
  <?php todo('Add survey header at top of the internal error page') ?>
    <div style='width:80%; max-width:600px; margin-top:5%; margin-left:auto; margin-right:auto;'>
      <a href='<?=$dir?>/index.php'>
        <img src='<?=$dir?>/img/500.png' alt='Something went terribly wrong' style='width:100%;'>
      </a>
    </div>
    <div style='margin-top:10px; font-size:large; text-align:center;'>
      Please contact <?=ADMIN_CONTACT?> and let <?=ADMIN_PRONOUN?> something is amiss.
    </div>
<?php if(isset($errid)) { ?>
    <div style='margin-top:8px; color:#202020; text-align:center;'>
      <i>And if you could mention error </i><span style='color:darkred;'>#<?=$errid?></span><i>, that may be helpful</i>
  </body>
<?php } ?>
</html>
