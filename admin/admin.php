<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/elements.php'));
require_once(app_file('admin/elements.php'));

log_dev("-------------- Start of Admin Dashboard --------------");
log_dev("REQUEST: ".print_r($_REQUEST,true));

// If an explicit request was made to login as site admin,
//   handle that now.  No need to verify existing admin/user roles.

if(($_REQUEST['admin']??'') === 'login') {
  require(app_file('admin/login.php'));
  die();
}

// See if we're already logged in as site admin.  If so, enable
//   all roles (admin, content, and tech)
// Otherwise, see if the active user is the primary survey admin.
//   If so, again enable all roles.
// Otherwise, if there is an active user, enable only those roles
//   which the active user has been granted.
// If none of the above... disable all roles

$admin_id = $_SESSION['admin-id'] ?? null;
$userid = active_userid();

$active_roles = [];
if($admin_id) {
  $active_roles = ['admin','content','tech'];
} else if($userid) {
  if($userid===primary_admin()) {
    $active_roles = ['admin','content','tech'];
  } else {
    $active_roles = user_roles($userid);
  }
}

// If there are no enabled roles, update the status to indicate
//   that active user does not have any defined admin roles 
//   (if applicable) and jump to the admin login page.
if(!$active_roles) {
  if(isset($userid)) {
    set_warning_status("$userid does not have access to Admin Dashboard");
  }
  require(app_file('admin/login.php'));
  die();
}

//  If a request to download or display the log in new tab,
//    and the the 'tech' role is enabled, handle the log
//    request and be done.  (Do not load the admin dashboard)
if(key_exists('log',$_REQUEST) && in_array('tech',$active_roles)) {
  require(app_file('admin/log.php'));
  die();
}

//  If a request to download a pdf file and the 'content' role
//    is enabled, handle the pdf download request and be done.
if(key_exists('pdf',$_REQUEST) && in_array('content',$active_roles)) {
  require(app_file('admin/pdf.php'));
  die();
}

// If we got here, then there is at least one enabled role, we can
//   proceed with populating the admin dashboard.
//
// But first, we need to determine which tabs to show based on the
//   enabled roles.

$tabs = [
  'settings' => [],
  'roles' => ['admin'],
  'surveys' => ['admin','content'],
  'log' => ['admin','tech'],
];

if($admin_id || $userid===primary_admin()) {
  $active_tabs = array_keys($tabs);
} else {
  $active_tabs = [];
  foreach($tabs as $tab=>$required_roles) {
    if(array_intersect($required_roles,$active_roles)) {
      $active_tabs[] = $tab;
    }
  }
}

// If there are no tabs available to the active user, then 
//   this is equivalent to not having any admin role... 
//   Go to the admin login page.

if(!$active_tabs) {
  set_warning_status("$userid does not have access to Admin Dashboard");
  require(app_file('admin/login.php'));
}

// If there was a requested tab, honor that request IF at least
//  one required role for that tab is satified... Otherwise, 
//  select the first tab available to the active user.

$cur_tab = $_REQUEST['tab'] ?? '';
if(!in_array($cur_tab,$active_tabs)) {
  $cur_tab = $active_tabs[0];
}

// Everything that follows handle populating the admin dashboard content
//   for the current tab

$tab_css = css_uri($cur_tab,'admin');

start_page('admin',
  [ 'css'=>css_uri($cur_tab,'admin'), ]
);

$form_uri = app_uri('admin');
$nonce = gen_nonce('admin-navbar');

echo "<!-- Admin Tabs -->";
echo "<form id='admin-tabs' class='admin-navbar' method='post' action='$form_uri'>";
add_hidden_input('ajaxuri',app_uri());
add_hidden_input('nonce',$nonce);

echo "<div class='tabs'>";
foreach($active_tabs as $tab)
{
  $disabled = ($cur_tab === $tab) ? "disabled class='active'" : '';
  echo "<button $disabled name='tab' value='$tab'>$tab</button>";
}
if($admin_id) {
  echo "<a class='admin logout'>Logout Admin</a>";
} else {
  echo "<span class='userid'>($userid)</span>";
  echo "<a class='admin login'>Login as Admin</a>";
}
echo "</div>";
echo "</form>";

$img=img_uri("icons8-info.png");
echo <<<HTMLMODAL
<!-- Tab Switch Modal -->
<div id='tab-switch-modal'>
  <div id='tab-switch-content'>
    <img src='$img'>
    <div class='text-box'>
      <p>You have unsaved changes.</p>
      <p>If you switch <span class='tsm-type'>tabs</span>, you will lose your changes.</p>
    </div>
    <div class='tab-switch-buttons'>
      <button class='confirm'>Switch Tabs</button>
      <button class='cancel'>Stay Here</button>
    </div>
  </div>
</div>
HTMLMODAL;


echo "<div class='body'>";

require(app_file("admin/{$cur_tab}_tab.php"));

echo "</div>";
end_page();


