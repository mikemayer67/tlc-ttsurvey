<?php
$dir = preg_replace("/\/[^\/]+\.php$/", "", $_SERVER['SCRIPT_NAME']);
$s = print_r($_SERVER,true);
?>

<html>
  <head>
    <title>Oops...</title>
  </head>
  <body>
    <div style='width:80%; max-width:600px; margin-top:5%; margin-left:auto; margin-right:auto;'>
      <a href='<?=$dir?>/index.php'>
        <img src='<?=$dir?>/img/405.png' alt='Click here to return to the survey' style='width:100%;'>
      </a>
    </div>
    <pre><?=$s?></pre>
  </body>
</html>
