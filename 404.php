<?php

$base_uri = false;
$key = "BASE_URI";
for($i=0; $i<10; $i++) {
  if(array_key_exists($key,$_SERVER)) {
    $base_uri = $_SERVER[$key];
    break;
  }
  $key = "REDIRECT_$key";
}
$code = http_response_code();
if(empty($code)) {
  $code = 404;
  http_response_code($codee);
}

echo "<html>";
echo "  <head>";
echo "    <title>$code</title>";
echo "  </head>";
echo "  <body>";
if(empty($base_uri)) {
  echo("You appear lost...");
} else {
  $base_uri = rtrim($base_uri,'/');
  echo "    <div style='width:80%; max-width:600px; margin-top:5%; margin-left:auto; margin-right:auto;'>";
  echo "      <a href='$base_uri/index.php'>";
  echo "        <img src='$base_uri/img/404.png' alt='Click here to return to the survey' style='width:100%;'>";
  echo "      </a>";
  echo "    </div>";
}
echo "  </body>";
echo "</html>";
