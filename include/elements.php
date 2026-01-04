<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/settings.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/status.php'));
require_once(app_file('include/login.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/roles.php'));

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

function add_hidden_input($name,$value)
{
  echo "<input type='hidden' name='$name' value='$value'>";
}

function add_hidden_submit($name,$value)
{
  echo "<input type='submit' class='hidden' name='$name' value='$value'>";
}

// Common start/end page functions
//   Note that most start_xxx_page functions are found in xxx/elements.php files
//   The notable exception is start_fault_page as there is not really an appropriate
//   xxx/elements.php for it

function start_fault_page($context)
{
  start_header();
  add_tab_name('ttt_survey');
  add_css_resources($context);
  end_header();

  add_navbar($context); 

  start_body();
}

function end_page()
{
  // close the body and html elements
  echo "<div class='spacer' style='width:0; min-width:0;'></div>";
  echo "</div>\n";  // #ttt-body

  // Add a footer that acknowledges the use of icons from icons8.com
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

function start_header($title=null)
{
  $base  = base_uri();
  $title = $title ?? active_survey_title() ?? app_name();

  $google_fonts = "https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&family=Noto+Serif+Display:ital,wght@0,100..900;1,100..900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&family=Quicksand:wght@300..700&display=swap";

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

function add_tab_name($tab_name)
{
  echo "<script>window.name='$tab_name';</script>";
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
  echo "<div class='spacer' style='width:0; min-width:0;'></div>";
}

function add_navbar($context,$kwargs=[])
{
  $userid = $kwargs['userid'] ?? null;

  echo "<!-- Navbar -->";
  // include both a wrapper and the navbar itself for purpose of css layout of the user menu
  echo "<div id='ttt-navbar-wrapper'>";
  echo "<div id='ttt-navbar'>";

  // left (title) box
  echo "<div class='left-box title-box'>";
  $rc_left = call_context_function('add_navbar_left',$context,$kwargs);
  echo "</div>";

  // center (status) box
  echo "<div class='center-box status'>";
  $rc_center = call_context_function('add_navbar_center',$context,$kwargs);
  echo "</div>";

  // right (username) box
  $user = null;
  echo "<div class='right-box username'>";
  $rc_right = call_context_function('add_navbar_right',$context,$kwargs);
  echo "</div>"; // right-box

  echo "</div>"; // navbar

  if($rc_right['user_menu'] ?? false) { add_nojs_user_menu(); }

  echo "</div>"; // wrapper
}

function add_navbar_left($kwargs)
{
  $logo_file = app_logo();
  $logo_uri  = $logo_file ? img_uri($logo_file) : '';

  $title = $kwargs['title']  ?? active_survey_title() ?? app_name();

  if($logo_uri) { echo "<img class='ttt-logo' src='$logo_uri' alt='Logo'>"; }
  echo "<span class='ttt-title'>$title</span>";
}

function add_navbar_center($kwargs)
{
  echo $kwargs['status'] ?? '';
}

function add_navbar_right($kwargs) 
{
}

function add_return_to_survey()
{
  $survey_icon = img_uri('icons8/survey.png'); 
  $survey_uri = app_uri();
  echo "<span class='survey link'>";
  echo "<a href='$survey_uri' target='ttt_survey'>";
  echo "<img class='survey link' src='$survey_icon' alt='survey' target='ttt_survey'>";
  echo "<span class='link-tip'>Return to Survey</span>";
  echo "</a>";
  echo "</span>";
}

function add_user_menu($userid)
{
  $user = User::from_userid($userid) ?? null;
  if(!$user) { return false; }

  $roles = user_roles($userid);
  if($roles) {
    $admin_icon = img_uri('icons8/settings.png'); 
    $admin_uri = app_uri('admin');
    echo "<span class='admin link'>"; echo "<a data-href='$admin_uri'>";
    echo "<img class='admin link' src='$admin_icon' alt='Dashboard'>";
    echo "<span class='link-tip'>Admin Dashboard</span>";
    echo "</a>";
    echo "</span>";

    // The following is a little bit hackish, but it gets the job done.
    //   This modifies the admin dashboard link to only function when JS is enabled
    //   and to provide a hint in the tooltip that JS is required when disabled.
    echo "<noscript><style>";
    echo "img.admin.link { pointer-events:none; opacity:0.3; cursor:default }\n";
    echo "span.admin.link .link-tip::after {";
    echo "  content:'\A(requires Javascript)';";
    echo "  font-size: 0.8em; color:darkred; margin-left:0.2em;";
    echo "  display:block;";
    echo "}\n";
    echo "</style></noscript>";
  }
  if(has_summary_access($userid)) {
    $summary_icon = img_uri('icons8/summary.png'); 
    $summary_uri = app_uri('summary');
    echo "<span class='summary link'>";
    echo "<a href='$summary_uri' target='ttt_summary'>";
    echo "<img class='summary link' src='$summary_icon' alt='Summary' target='ttt_summary'>";
    echo "<span class='link-tip'>Survey Summary</span>";
    echo "</a>";
    echo "</span>";
  }
  $username = $user->fullname();
  echo "<span>$username</span>";
  add_menu_trigger($user);

  return true;
}

function add_menu_trigger($user) 
{
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

function add_nojs_user_menu()
{
  $logout_uri = app_uri("logout");
  $nonce = gen_nonce('update-page');
  $updatepw_uri = app_uri("update=updatepw&ttt=$nonce");
  $updateprof_uri = app_uri("update=updateprof&ttt=$nonce");
  echo "<noscript>";
  echo "<input id='nojs-menu-toggle' type='checkbox'>";
  echo "<div id='ttt-user-menu'>";
  echo "<a class='user-menu-item' href='$updateprof_uri' target='ttt_blank'>edit profile</a>";
  echo "<a class='user-menu-item' href='$updatepw_uri' target='ttt_blank'>edit password</a>";
  echo "<a class='user-menu-item' href='$logout_uri'>logout</a>";
  echo "</div>";
  echo "</noscript>";
}

function add_js_required()
{
  echo "<!-- Javascript required -->";
  echo "<noscript>";
  echo "<div class='noscript card'>";
  echo "<div class='ttt-card'>Javascript is required for the Admin Dashboard</div>";
  echo "</div>";
  echo "</noscript>";
}

function add_js_recommended($wrapper="noscript")
{
  $dismiss_icon = img_uri('icons8/dismiss.png');
  echo "<!-- Javascript suggestion -->";
  echo "<input id='nojs-toggle' type='checkbox' checked></input>";
  echo "<$wrapper>";
  echo "<div class='noscript'>";
  echo "  <div>Consider enabling JavaScript for a smoother interaction with the survey</div>";
  echo "  <div>Incremental saves without submitting an incomplete form</div>";
  echo "  <div>Easier to update your profile info</div>";
  echo "  <label for='nojs-toggle'><img class='dismiss' src='$dismiss_icon' alt='dismiss'></img></label>";
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

// The following is mostly needed in login/elements, but it is aslo needed here for the no-js user menu

function login_info_lines($key) 
{
  switch($key) {
  case 'userid':
    return [
      "Used to log into the survey",
      "must be 8-16 characters",
      "must start with a letter",
      "must contain only letters and numbers",
    ];
    break;

  case 'new-password':
  case 'password':
    return [
      "Used to log into the survey",
      "must be 8-128 characters",
      "must contain at least one letter",
      "may contain: !@%^*-_=~,.",
      "may contain spaces",
    ];
    break;

  case 'fullname':
    return [
      "How your name will appear on the survey summary report",
      "must contain a valid full name",
      "may contain apostrophes",
      "may contain hyphens",
      "Extra whitespace will be removed",
    ];
    break;

  case 'email':
    return [
      "The email address is optional. It will only be used in conjunction with this survey."
     ." It will be used to send you:",
      "confirmation of your registration",
      "notifcations on your survey state",
      "login help (on request)",
    ];
    break;

  case 'remember':
    return [
      "Sets a cookie on your browser to allow you to resume the survey without a password",
    ];
    break;

  case 'recover-userid':
    return [
      "If the profile for this userid has an associated email address, instructions"
      ." for resetting your password will be sent to that address:",
      "If a userid is provided here, the email address below will be ignored",
    ];
    break;

  case 'recover-email':
    return [
      "If a user pofile associated with this email address exists, the userid and instructions"
     ." for resetting your password will be sent to this address.",
      "If a userid is provided above, the email address here will be ignored",
    ];
    break;
  }
  return [];
}

function login_info_string($key) 
{
  $lines = login_info_lines($key);
  return implode("\n    ",$lines);
}

function login_info_html($key)
{
  $lines  = login_info_lines($key);
  $header = htmlspecialchars( array_shift($lines) ?? '' );
  $lines  = array_map( fn($line) => htmlspecialchars($line),          $lines);
  $lines  = array_map( fn($line) => "<p class='info-list'>$line</p>", $lines);
  return $header . implode("",$lines);
}
