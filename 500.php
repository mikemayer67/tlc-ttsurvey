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
echo "<a href='".app_uri()."' target='ttt_survey'>";
$img = img_tag('500.png','','Something went terribly wrong');
echo $img;
echo "</a>";

$action = app_uri();
$nonce = gen_nonce('bug-reporting');

echo "<form id='bug-reporting' method='post' action='$action'>";
echo "<div class='bug-form'>";
echo "<input type='hidden' name='nonce' value='$nonce'>";
echo "<input type='hidden' name='bug-report' value='1'>";
if(isset($errid)) {
  echo "<input type='hidden' name='errid' value='$errid'>";
}
echo "<div class='instructions'>";
echo <<<INSTRUCTIONS
  Please take a minute to tell us what you were trying to do when things went
  off the rails.  This will help us diagnose and fix the issue.
INSTRUCTIONS;
echo "</div>";
echo "<textarea placeholder='What was going on when this happened?' required></textarea>";
echo "<div class='submit-bar'>";
echo "<button type='submit' name='action' value='submit'>Submit</button>";
echo "<button type='submit' name='action' value='cancel' formnovalidate>No Thanks</button>";
echo "</div>";
echo "</div>";
echo "</form>";

end_page();
