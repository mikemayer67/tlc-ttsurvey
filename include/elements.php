<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/settings.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/status.php'));
require_once(app_file('include/login.php'));
require_once(app_file('include/users.php'));
require_once(app_file('login/elements.php'));

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

// Common page setup function for different contexts

function start_login_page()
{
  log_dev("start_login_page()");
  $context = 'login';
  start_header();
  add_js_resources($context);
  add_css_resources($context);
  end_header();
  add_navbar($context);
  add_js_recommended();
  add_status_bar();
  start_body();
}

function start_admin_page($cur_tab=null)
{
  log_dev("start_admin_page()");
  $context = 'admin';

  start_header();

  add_js_resources($context);
  if($cur_tab) { // admin tab page
    add_css_resources($context, css_uri($cur_tab,'admin') );
  } else { // admin login page
    add_css_resources($context, css_uri('login'), css_uri('login','admin') );
  }

  end_header();

  add_navbar($context);
  add_js_required();
  add_admin_lock();
  add_status_bar();

  start_body();
}

function start_survey_page($title,$userid)
{
  log_dev("start_survey_page()");
  $context = 'survey';

  start_header($title);

  add_js_resources($context, js_uri('jquery_helpers'));
  add_css_resources($context);

  end_header();

  add_navbar($context, $userid, $title, '[status]');
  add_js_recommended();
  add_status_bar();

  start_body();
}

function start_preview_page($title,$userid,$enable_js=true)
{
  log_dev("start_preview_page()");
  $context = 'survey';

  start_header($title);

  if($enable_js) { 
    add_js_resources($context, js_uri('jquery_helpers')); 
  }
  add_css_resources($context);

  end_header();

  add_navbar($context, $userid, $title, 'Preview');
  add_js_recommended($enable_js ? 'noscript' : 'div');
  add_status_bar();

  start_body();
}

function start_nosurvey_page()
{
  log_dev("start_nosurvey_page()");
  $context = 'survey';

  start_header();

  add_js_resources($context, js_uri('jquery_helpers'));
  add_css_resources($context);

  end_header();

  $userid = active_userid() ?? null;
  add_navbar($context,$userid);
  add_js_recommended();

  start_body();
}


function start_fault_page($context)
{
  log_dev("start_fault_page($context)");

  start_header();

  add_css_resources($context);
  end_header();
  add_navbar($context); 

  start_body();
}

// The end_page function simply closes the ttt-boday <div>, <body>, and <html>
//   - it also adds a footer that acknowleges the use of icons from icons8.com.
function end_page()
{
  // close the body and html elements
  echo "</div>\n";  // #ttt-body

  echo "<!-- Footer -->";
  echo "<div id='ttt-footer'>";
  echo "  <span class='ttt-ack'>";
  echo "    icons by <a href='https://icons8.com' target='_blank'>icons8</a>";
  echo "  </span>";
  echo "</div>";

  // close the html elements
  echo "</body>\n"; // html body
  echo "</html>\n";
}

function start_header($title = null)
{
  $base  = base_uri();
  $title = $title ?? active_survey_title() ?? app_name();

  $google_fonts = "https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&family=Noto+Serif+Display:ital,wght@0,100..900;1,100..900&display=swap";

  echo "<!DOCTYPE html><html><head>";
  echo "<meta charset='UTF-8'>";
  echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<base href='$base'>";
  echo "<!-- Google Fonts -->";
  echo "<link rel='preconnect' href='https://fonts.googleapis.com'>";
  echo "<link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>";
  echo "<link href='$google_fonts' rel='stylesheet'>";
  echo "<!-- Title -->";
  echo "<title>$title</title>";
}

function add_js_resources($context, ...$extra_js)
{
  //  - always include jquery
  //  - add context specific URI if js file exists
  //  - add any extra js that may have been provided
  $js_uris = array_merge(
    [ js_uri('jquery-3.7.1.min') ],
    $extra_js,
    file_exists(app_file("$context/js/$context.js")) ? [js_uri($context,$context)] : [],
  );
  echo "<!-- Javascript -->";
  foreach($js_uris as $js_uri) {
    echo "<script src='$js_uri'></script>";
  }
  echo "<script>const ttt_menu_icon='".img_uri('icons8/menu.png')."';</script>";
}

function add_css_resources($context, ...$extra_css)
{
  //  - always include base CSS
  //  - add context specific URI if css file exists
  //  - add any extra css that may have been provided
  $css_uris = array_merge(
    [css_uri('ttt')],
    file_exists(app_file("css/$context.css")) ? [css_uri($context)] : [],
    $extra_css
  );

  echo "<!-- Style -->";
  foreach ($css_uris as $uri) {
    echo "<link rel='stylesheet' type='text/css' href='$uri'>";
  }
}

function end_header()
{
  // ends the HTML header
  // starts the HTML body
  echo "</head><body>";
}

function start_body()
{
  // starts the ttt body
  echo "<div id='ttt-body'>";
}

function add_navbar($context,$userid=null,$title=null,$status='')
{
  $title = $title ?? active_survey_title() ?? app_name();
  $logo_file = app_logo();
  $logo_uri  = $logo_file ? img_uri($logo_file) : '';

  echo "<!-- Navbar -->";
  // include both a wrapper and the navbar itself for purpose of css layout of the user menu
  echo "<div id='ttt-navbar-wrapper'>";
  echo "<div id='ttt-navbar'>";

  // title box
  echo "<span class='ttt-title-box'>";
  if($logo_uri) { echo "<img class='ttt-logo' src='$logo_uri' alt='Logo'>"; }
  echo "<span class='ttt-title'>$title</span>";
  echo "</span>";

  // status
  echo "<span class='status'>$status</span>";

  // User Info
  echo "<span class='username'>";
  $user = User::from_userid($userid) ?? null;
  if($user) {
    $username = $user->fullname();
    echo "<span>$username</span>";
    add_menu_trigger($context,$user);
  }
  echo "</span>";

  echo "</div>"; // navbar

  add_nojs_user_menu($context);

  echo "</div>"; // wrapper
}

function add_menu_trigger($context,$user) 
{
  // No-Javascript user menu

  // @@@ TODO: Modify this to take place of javascript user menu
  //   Will include profile/password items in survey context
  //----------
  //  $logout_uri = app_uri('logout');
  //  echo "<noscript>";
  //  echo "<a id='ttt-logout' href='$logout_uri'>logout</a>";
  //  echo "</noscript>";
  //----------

  $menu_icon = img_uri('icons8/menu.png');
  echo "<noscript>";
  echo "<div class='menu-trigger'>";
  echo "<label for='nojs-menu-toggle'>";
  echo "<img class='menu-trigger' src='$menu_icon' alt='User Menu'>";
  echo "</label>";
  echo "</div>";
  echo "</noscript>";

  // Javascript enabled user menu

  $icons = [
    'show' => img_uri('icon8/show_pw.png'),
    'hide' => img_uri('icon8/hide_pw.png'),
  ];

  $hints = [
    'name'     => login_info_string('fullname'),
    'email'    => login_info_string('email'),
    'password' => login_info_string('password'),
  ];

  $user_info = [
    'userid' => $user->userid(),
    'name'   => $user->fullname(),
    'email'  => $user->email(),
  ];

  echo "<script>";
  echo "const ttt_icons = ".json_encode($icons).";";
  echo "const ttt_hints = ".json_encode($hints).";";
  echo "const ttt_user  = ".json_encode($user_info).";";
  echo "</script>";
}

function add_nojs_user_menu($context)
{
  echo "<noscript>";
  echo "<input id='nojs-menu-toggle' type='checkbox'>";
  echo "<div id='ttt-user-menu'>";
  echo "<a class='user-menu-item'>edit profile</button>";
  echo "<a class='user-menu-item'>edit password</button>";
  echo "<a class='user-menu-item'>logout</button>";
  echo "</div>";
  echo "</noscript>";
}

function add_js_required()
{
  echo "<!-- Javascript required -->";
  echo "<noscript>";
  echo "<div class='noscript'>";
  echo "<div class='ttt-card'>Javascript is required for the Admin Dashboard</div>";
  echo "</div>";
  echo "</noscript>";
}

function add_js_recommended($wrapper="noscript")
{
  echo "<!-- Javascript suggestion -->";
  echo "<$wrapper>";
  echo "<div class='noscript'>";
  echo "  <div>Consider enabling JavaScript for a smoother interaction with the survey</div>";
  echo "  <div>Less likely to lose your progress by leaving this page.</div>";
  echo "  <div>Easier to update your name or email</div>";
  echo "</div>";
  echo "</$wrapper>";
}

function add_admin_lock()
{
  require_once(app_file('admin/admin_lock.php'));
  $lock = json_encode(obtain_admin_lock());
  echo "<div id='ttt-small-screen'>The Admin Dashboard is not intended for use on small screens</div>";
  echo "<script>var admin_lock = $lock;</script>";
}

function add_status_bar()
{
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

