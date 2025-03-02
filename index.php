<?php
require_once("./include/fix_env.php");
$base_dir = $_SERVER['BASE_DIR'];
?>

  <h1>You are in the right place (<?=$base_dir?>)</h1>

<pre> <?php print_r($_SERVER); ?> </pre>
