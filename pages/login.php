<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("Loading Login Page");
require_once(app_file('include/users.php'));
require_once(app_file('include/page_elements.php'));

start_page('login');
navbar();
?>


<?php
end_page();


