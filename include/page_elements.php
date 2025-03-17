<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/const.php'));
require_once(app_file('include/settings.php'));
require_once(app_file('include/surveys.php'));


function img_tag($filename,$class='',$alt='')
{
  if(!$filename) { return ''; }

  $img_uri = app_uri("img/$filename");
  if($class) { $class = "class='$class'"; }
  if($alt)   { $alt   = "alt='$alt'"; }

  return "<img $class src='$img_uri' $alt>";
}
function add_img_tag($filename,$class='',$alt='') { 
  echo img_tag($filename,$class,$alt); 
}

function link_tag($href,$body,$class='')
{
  if($class) { $class = "$class='$class'"; }
  $href = app_uri($href);

  return "<a href='$href' $class>$body</a>";
}
function add_link_tag($filename,$class='',$alt='') {
  echo link_tag($filename,$class,$alt); 
}

todo("Update the following commentary on start_page function");
// The start_page function adds all theh motherhood and apple pie that belongs
//   at the start of any web page (<html>, <head>, <title>, <body>, etc.).
//
// The tile will be set to the value APP_TITLE regardless of the content of the page.
//
// Unless noted to the contrary below, it will also add some resources common
//   to most of the pages served by the ttsuvey app (jQuery, the app's primary css, etc.)
//
// In addition, it will include resources based on the specified "flavor" of the page
//
// The flavors include:
//    print   - Don't include any css or javascript
//    login   - Includes the css associated with user login
//
function start_page($flavor,$kwargs=[])
{
  // just in case...
  $flavor = strtolower($flavor);

  echo "<!DOCTYPE html><html><head>\n";

  echo "<meta charset='UTF-8'>\n";
  echo "<meta name='viewport' content='width=device-width, initial-scale=1'>\n";

  $title = active_survey_title() ?? DEFAULT_TITLE;
  echo "<title class=tlc-title>$title</title>\n";

  // don't include css or javascript in pages that are displayed for printing purposes
  if($flavor == 'print') {
    // close the body and html elements and return
    echo "</head></body>\n";
    return;
  }

  // in order to prevent css files from caching, we append a changing query
  // string to the URL for the css file... but this should only be needed
  // in a development environment.
  $v = is_dev() ? rand() : 0;

  $title_len = strlen($title);
  echo "<style> *{--n-title-chars:$title_len}</style>";

  echo "<script src='https://code.jquery.com/jquery-3.7.1.min.js' ";
  echo "integrity='sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=' ";
  echo "crossorigin='anonymous'></script>\n";

  echo "<link rel='stylesheet' type='text/css' href='", APP_URI, "/css/w3.css?v=$v'>\n";
  echo "<link rel='stylesheet' type='text/css' href='", APP_URI, "/css/ttt.css?v=$v'>\n";

  switch($flavor) {
  case 'login':
    echo "<link rel='stylesheet' type='text/css' href='", APP_URI, "/css/login.css?v=$v'>\n";
    break;
  default:
    break;
  }

  // close the head element and open the body element
  echo "</head><body>";

  // Add the navigation bar
  if( $kwargs['navbar'] ?? true ) {
    echo "<!-- Navbar -->\n";
    echo "<div class='ttt-navbar'>\n";
    echo "<span class='ttt-title-box'>";
    echo img_tag(NAVBAR_LOGO,"ttt-logo");
    echo "<span class='ttt-title'>$title</span>";
    echo "</span>\n";
    $menu_cb = $kwargs['navbar-menu-cb']??null;
    if($menu_cb) { $menu_cb(); }
    echo "</div>\n\n";
  }

  // Start the container for survey body
  echo "<div id='ttt-body'>";
}

function end_page()
{
  // close the body and html elements
  echo "</div>\n";  // #ttt-body
  echo "</body>\n"; // html body
  echo "</html>\n";
}


