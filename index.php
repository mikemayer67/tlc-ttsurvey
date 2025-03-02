<?php
namespace tlc\tts;

// All interaction with the tlc-ttsurvey app should be funneled through this file.
// First thing we want to do is set up our common initialization for ll calls

require_once("./include/init.php")
?>

<h1>You are in the right place (<?=BASE_URI?>)</h1>
<h2><?=APP_DIR?></h2>

<pre> <?php print_r($_SERVER); ?> </pre>
