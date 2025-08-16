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
//   Will determine which css file to load.
//
// To support printable pages, the css flavor of "print" will suppress the
//   loading of any css (or javascript) ressources.
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

