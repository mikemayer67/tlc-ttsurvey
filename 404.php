<?php
require_once("./include/fix_env.php");
$base_dir = rtrim($_SERVER['BASE_DIR'],'/');
?>

<html>
  <head>
    <title>404</title>
  </head>
  <body>
    <div style="width:80%; max-width:600px; margin-top:5%; margin-left:auto; margin-right:auto;">
      <a href='<?=$base_dir?>/index.php'>
        <img src='<?=$base_dir?>/img/404.png' alt='Click here to return to the survey' style="width:100%;">
      </a>
    </div>
  </body>
</html>

