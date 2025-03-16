<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/page_elements.php'));

start_page('no_survey');
?>

<div id='no-survey'>
<header>There is no active survey at this time</header>
<img src='img/coming_soon.png'>
</div>

<?php
end_page();
