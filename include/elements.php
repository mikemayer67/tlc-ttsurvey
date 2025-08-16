<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/settings.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/status.php'));

function safe_html(string $string): string {
  return htmlspecialchars(
    $string, 
    ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 
    'UTF-8'
  );
}

function img_tag($img,$class='',$alt='')
{
  if(!$img) { return ''; }

  $img = img_uri($img);
  if($class) { $class = "class='$class'"; }
  if($alt)   { $alt   = "alt='$alt'"; }

  return "<img $class src='$img' $alt>";
}
function add_img_tag($img,$class='',$alt='') { 
  echo img_tag($img,$class,$alt); 
}

function link_tag($href,$body,$class='')
{
  if($class) { $class = "$class='$class'"; }
  return "<a href='$href' $class>$body</a>";
}
function add_link_tag($href,$class='',$alt='') {
  echo link_tag($href,$class,$alt); 
}

function add_hidden_input($name,$value)
{
  echo "<input type='hidden' name='$name' value='$value'>";
}

function add_hidden_submit($name,$value)
{
  echo "<input type='submit' class='hidden' name='$name' value='$value'>";
}


// The start_page function adds all theh motherhood and apple pie that belongs
//   at the start of any web page (<html>, <head>, <title>, <body>, etc.).
//
// The page title is set to one of the following:  (in order)
//   - value specified in kwargs (survey_title)
//   - active survey title
//   - the name of the survey application as set in Admin/settings
//
// Conditionally adds loading of javascript resource 
//   - never loads js resources if context is 'print'
//   - always loads js resources if context is 'admin'
//   - otherwise, load js resources unless overridden in kwargs (js_enabled)
//   Loads:
//   - jquery
//   - $context/js/$context.js  (if it exists)
//
// Conditionally adds loading of css resources
//   - never loads css resources if context is 'print'
//   Loads:
//   - css/ttt.php
//   - css/$context.css (if it exists)
//   - any css URI listed in the kwargs (css)
//
// Closes the header and opens the body
//
// Conditioinally adds a navigation bar
//   - loads unless overridden by kwargs (navbar=false)
//   - includes the survey app logo if specified in Admin/Settings
//   - includes the survey title (see above)
//   - adds navbar items (most likely menus) specified in kwargs (navbar-menu-cb)
//     - is included in kwargs, the specified callback is invoked
//     - that callback is pretty much free to do whatever it can with the DOM
//
// Conditionally adds noscript content
//   - if context is 'admin', displays a message that the Admin Dashboard requires javascript
//   - If context is 'print', no noscript is added
//   - Otherwise, a suggestion is displayed suggeting use of javascript for a richer experience
//
// Conditinally initializes the admin lock mechanism if context is 'admin'
//
// Adds a status bar (at the top of the page) unless overridden by kwargs (status=false)
//   - initially hidden if there is no current status message to be shown
//   - initially visible/stylized if there is a current status message to be shown
//   - subsequently, The appearance and visisbility of this status bar is handled via js logic
//
// Opens a div element with id of ttt-body.
//   - This will be THE container for the survey content.
//   - It will be closed/finalized by the end_page function
//
function start_page($context,$kwargs=[])
{
  $context = strtolower($context);

  $title = $kwargs['survey_title'] ?? active_survey_title() ?? app_name();
  $title_len = strlen($title);

  $base = base_uri();

  log_dev("start_page($context)");
  $trace = debug_backtrace();

  echo <<<HTMLHEAD
  <!DOCTYPE html><html><head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <base href='$base'>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&family=Noto+Serif+Display:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <!-- Title -->
  <title>$title</title>
  HTMLHEAD;

  // Add javascript resources
  //  - exclude if context is print
  //  - require if context is admin
  //  - all other contexts
  //     - exclude if explicitly excluded by kwargs
  //     - include otherwise
  switch($context) {
  case 'print':  $include_js = false; break;
  case 'admin':  $include_js = true;  break;
  default:       $include_js = ($kwargs['js_enabled'] ?? true); break;
  }

  if($include_js) {
    $js_uris = [ js_uri('jquery-3.7.1.min') ];

    if(file_exists( app_file("$context/js/$context.js")) ) { 
      $js_uris[] = js_uri($context,$context);
    }

    echo "<!-- Javascript -->";
    foreach($js_uris as $js_uri) {
      echo "<script src='$js_uri'></script>";
    }
  }

  // Add style resources (except for print context)
  //  - always include base CSS
  //  - add context specific URI if css file exists
  //  - add kwargs provided URIs
  if($context !== 'print') 
  {
    $css_uris = [ css_uri('ttt') ];

    if( file_exists(app_file("css/$context.css"))) { 
      $css_uris[] = css_uri($context); 
    }

    foreach( (array)($kwargs['css'] ?? []) as $css_uri ) { 
      $css_uris[] = $css_uri; 
    }

    echo "<!-- Style -->";
    foreach ($css_uris as $uri) {
       echo "<link rel='stylesheet' type='text/css' href='$uri'>";
    }
  }

  // close the head element and open the body element
  echo "</head><body>";

  // Add the navigation bar (unless explicitly excluded via the kwargs)
  if( $kwargs['navbar'] ?? true ) {
    echo "<!-- Navbar -->";
    echo "<div id='ttt-navbar'>";
    echo "<span class='ttt-title-box'>";

    $logo = app_logo() ?? '';
    if($logo) {
      echo "<img class='ttt-logo' src='".img_uri($logo)."' alt='Trinity Logo'>";
    }
    echo "<span class='ttt-title'>$title</span>";
    echo "</span>";

    $menu_cb = $kwargs['navbar-menu-cb'] ?? null;
    if($menu_cb) { echo $menu_cb(); }

    echo "</div>";
  }

  // Add noscript content
  //   - javascript is required for admin context
  //   - javascript is excluded for print context
  //   - javascript is recommended for all other contexts
  switch($context) 
  {
  case 'admin':
    echo <<<HTMLNOSCRIPT
    <!-- Javascript required -->
    <noscript>
    <div class='noscript'>
      <div class='ttt-card'>
        Javascript is required for the Admin Dashboard
      </div>
    </div>
    </noscript>
    HTMLNOSCRIPT;
    break;

  case 'print':
    break;

  default:
    echo <<<HTMLNOSCRIPT
    <!-- Javascript suggestion -->
    <noscript>
    <div class='noscript'>
      Consider enabling JavaScript for a smoother interaction with the survey
    </div>
    </noscript>
    HTMLNOSCRIPT;
    break;
  }
  
  // Add admin lock (only in admin context)
  if($context === 'admin') 
  {
    require_once(app_file('admin/admin_lock.php'));
    $lock = json_encode(obtain_admin_lock());
    echo <<<ADMINLOCK
    <div id='ttt-small-screen'>The Admin Dashboard is not intended for use on small screens</div>
    <script>
      var admin_lock = $lock;
    </script>
    ADMINLOCK;
  }

  // Add the status bar (unless explicitly excluded via the kwargs)
  if( $kwargs['status'] ?? true ) {
    echo "<!-- Status Bar -->";
    $status = get_status_message();
    if($status) {
      $level = $status[0];
      $msg   = $status[1];
      echo "<div id='ttt-status' class='$level'>$msg</div>";
    } else {
      echo "<div id='ttt-status' class='none'></div>";
    }
  }

  // Start the container for survey body
  echo "<div id='ttt-body'>";
}

// The end_page function simply closes the ttt-boday <div>, <body>, and <html>
//   - oh yeah... and it adds a footer that acknowleges the use of icons from icons8.com.
function end_page()
{
  // close the body and html elements
  echo "</div>\n";  // #ttt-body

  echo <<<HTMLFOOTER
    <!-- Footer -->
    <div id='ttt-footer'>
      <span class='ttt-ack'>
        icons by <a href='https://icons8.com' target='_blank'>icons8</a>
      </span>
    </div>
    HTMLFOOTER;

  // close the html elements
  echo "</body>\n"; // html body
  echo "</html>\n";
}

