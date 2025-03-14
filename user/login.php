<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("Loading Login Page");
require_once(app_file('include/users.php'));
?>

<h1>LOGIN</h1>

