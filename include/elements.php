<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/settings.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/status.php'));


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
  $title = active_survey_title() ?? app_name();
  $title_len = strlen($title);

  log_dev("start_page($context)");
  $trace = debug_backtrace();

  echo <<<HTMLHEAD
  <!DOCTYPE html><html><head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1'>
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&family=Noto+Serif+Display:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <!-- Title -->
  <title class=tlc-title>$title</title>
  HTMLHEAD;


  // don't include css or javascript on print pages
  if($context !== 'print') {
    $ttt_uri = css_uri('ttt');
    $context_uri = css_uri($context);
    $js_uri = js_uri($context,$context);
    $jq_uri = js_uri('jquery-3.7.1.min');

    echo <<<HTMLHEAD
    <!-- Javascript -->
    <script src='$jq_uri'></script>
    <script src='$js_uri'></script>

    <!-- Style -->
    <link rel='stylesheet' type='text/css' href='$ttt_uri'>
    <link rel='stylesheet' type='text/css' href='$context_uri'>
    HTMLHEAD;

    $css = $kwargs['css'] ?? null;
    if($css) {
      if(!is_array($css)) { $css = [$css]; }
      foreach ($css as $uri) {
        echo "<link rel='stylesheet' type='text/css' href='$uri'>";
      }
    }
  }

  // close the head element and open the body element
  echo "</head><body>";

  // Add the navigation bar
  //   include unless navbar=false is explicitly set in the kwargs
  if( $kwargs['navbar'] ?? true ) {
    $logo = app_logo() ?? '';
    logger("logo = $logo");
    if($logo) { 
      $logo = "<img class='ttt-logo' src='".img_uri($logo)."' alt='Trinity Logo'>";
    }
    logger("logo = $logo");

    $menu_cb = $kwargs['navbar-menu-cb'] ?? null;
    $menu = $menu_cb ? $menu_cb() : '';

    echo <<<HTMLNAVBAR
    <!-- Navbar -->
    <div id='ttt-navbar'>
      <span class='ttt-title-box'>
        $logo
        <span class='ttt-title'>$title</span>
      </span>
      $menu
    </div>
    HTMLNAVBAR;
  }


  if($context === 'admin') {
    require_once(app_file('admin/admin_lock.php'));
    $lock = json_encode(obtain_admin_lock());
    echo <<<HTMLADMIN
    <!-- Javascript required -->
    <noscript>
    <div class='noscript'>
      <div class='ttt-card'>
        Javascript is required for the Admin Dashboard
      </div>
    </div>
    </noscript>
    <div id='ttt-small-screen'>The Admin Dashboard is not intended for use on small screens</div>
    <script>
      var admin_lock = $lock;
    </script>
    HTMLADMIN;
  }
  elseif($context !== 'print') {
    echo <<<HTMLNOSCRIPT
    <!-- Javascript suggestion -->
    <noscript>
    <div class='noscript'>
      Consider enabling JavaScript for a smoother interaction with the survey
    </div>
    </noscript>
    HTMLNOSCRIPT;
  }

  // Add the status bar
  //   include unless status=false is explicitly set in the kwargs
  if( $kwargs['status'] ?? true ) {
    $status = get_status_message();
    if($status) {
      $level = $status[0];
      $msg   = $status[1];
    } else {
      $level = 'none';
      $msg = '';
    }
    echo <<<HTMLSTATUS
    <!-- Status Bar -->
    <div id='ttt-status' class='$level'>$msg</div>
    HTMLSTATUS;
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

