<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/page_elements.php'));

log_dev("Loading Login Page");

start_page('login');

echo "<h1>LOGIN</h1>";

end_page();


