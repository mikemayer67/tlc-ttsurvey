<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { define('APP_DIR',dirname(__file__)); }

require_once(APP_DIR.'/include/init.php');
require_once(app_file('include/elements.php'));

define('RENDERING_ERR_PHP',true);

$contact = admin_contact();
if(isset($errid)) {
  $contact = preg_replace("/'>/","?subject=Survey Error #$errid'>", $contact);
}

$url = dirname($_SERVER['SCRIPT_NAME']);
start_fault_page('500');

echo "<div class='ttt-splash'>";
add_link_tag(app_uri(),img_tag('500.png','','Something went terribly wrong'));

echo "<div class='ttt-caption'>";
echo "Please contact $contact and let them know something is amiss.";
echo "</div>";

if(isset($errid)) {
  echo "<div class='ttt-subcaption'>";
  echo "And if you could mention error<span class='ttt-red'>#$errid</span>, that may be helpful";
  echo "</div>";
}

end_page();
