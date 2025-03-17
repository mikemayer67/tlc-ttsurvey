<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/page_elements.php'));

log_dev("Loading Admin Dashboard");

start_page('admin');

echo "<h1>ADMIN</h1>";

end_page();


