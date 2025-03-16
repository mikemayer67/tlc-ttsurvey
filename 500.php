<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { define('APP_DIR',dirname(__file__)); }

require_once(APP_DIR.'/include/init.php');
require_once(app_file('include/const.php'));
require_once(app_file('include/page_elements.php'));

define('RENDERING_500_PHP',true);

$contact = ADMIN_CONTACT;
$pronoun = ADMIN_PRONOUN;
if(isset($errid)) {
  $contact = preg_replace("/'>/","?subject=Survey Error #$errid'>", $contact);
}

$url = dirname($_SERVER['SCRIPT_NAME']);
start_page('500');

print("<div class='ttt-splash'>");
print(link_tag('tt.php',img_tag('500.png','','Something went terribly wrong')));

print("<div class='ttt-caption'>");
print("Please contact $contact and let $pronoun know something is amiss.");
print("</div>");

if(isset($errid)) {
  print("<div class='ttt-subcaption'>");
  print("And if you could mention error<span class='ttt-red'>#$errid</span>, that may be helpful");
  print("</div>");
}

end_page();
