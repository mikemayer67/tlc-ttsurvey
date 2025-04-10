<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));

$demo_menu_cb = function() {
  foreach([1,2,4] as $i) {
    echo "<span>[Menu$i]</span>";
  }
};

start_page('junk',array('navbar-menu-cb'=>$demo_menu_cb));

echo "<h1>DEMO</h1>";

end_page();

