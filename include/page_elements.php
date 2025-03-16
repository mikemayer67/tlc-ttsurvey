<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/const.php'));
require_once(app_file('include/settings.php'));
require_once(app_file('include/surveys.php'));

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

  print "<!DOCTYPE html><html><head>\n";

  print "<meta charset='UTF-8'>\n";
  print "<meta name='viewport' content='width=device-width, initial-scale=1'>\n";

  $title = active_survey_title() ?? DEFAULT_TITLE;
  print "<title class=tlc-title>$title</title>\n";

  // don't include css or javascript in pages that are displayed for printing purposes
  if($flavor == 'print') {
    // close the body and html elements and return
    print("</head></body>\n");
    return;
  }

  // in order to prevent css files from caching, we append a changing query
  // string to the URL for the css file... but this should only be needed
  // in a development environment.
  $v = is_dev() ? rand() : 0;

  $title_len = strlen($title);
  print("<style> *{--n-title-chars:$title_len}</style>");

  print (
    "<script src='https://code.jquery.com/jquery-3.7.1.min.js' " .
    "integrity='sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=' " .
    "crossorigin='anonymous'></script>\n"
  );

  print "<link rel='stylesheet' type='text/css' href='".APP_URI."/css//w3.css?v=$v'>\n";
  print "<link rel='stylesheet' type='text/css' href='".APP_URI."/css//tt.css?v=$v'>\n";

  switch($flavor) {
  case 'login':
    print "<link rel='stylesheet' type='text/css' href='".APP_URI."/css//login.css?v=$v'>\n";
    break;
  default:
    break;
  }

  // close the head element and open the body element
  print ("</head><body>");

  // Add the navigation bar
  if( $kwargs['navbar'] ?? true ) {
    print("<!-- Navbar -->\n");
    print("<div class='tt-navbar'>\n");
    print("<span class='tt-title-box'>");
    add_img_tag(NAVBAR_LOGO,"tt-logo");
    print("<span class='tt-title'>$title</span>");
    print("</span>\n");
    if( $kwargs['navbar-menu-cb']??null ) { $menu_cb(); }
    print("</div>\n\n");
  }

  // Start the container for survey body
  print("<div id='tt-body'>");
}

function end_page()
{
  // close the body and html elements
  print("</div>\n");  // #tt-body
  print("</body>\n"); // html body
  print("<</html>\n");
}


