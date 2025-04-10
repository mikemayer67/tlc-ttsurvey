<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/elements.php'));
require_once(app_file('admin/elements.php'));

log_dev("-------------- Start of Admin Dashboard --------------");
log_dev("GET: ".print_r($_GET,true));
log_dev("POST: ".print_r($_POST,true));

if(($_GET['admin']??'') === 'login') {
  require(app_file('login/admin.php'));
  die();
}

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

if(!$active_roles) {
  if(isset($userid)) {
    set_warning_status("$userid does not have access to Admin Dashboard");
  }
  require(app_file('login/admin.php'));
  die();
}

if(key_exists('log',$_REQUEST) && in_array('tech',$active_roles)) {
  require(app_file('admin/log.php'));
  die();
}

$tabs = [
  'settings' => [],
  'roles' => ['admin'],
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
if(!$active_tabs) {
  set_warning_status("$userid does not have access to Admin Dashboard");
  require(app_file('login/admin.php'));
}

$cur_tab = $_REQUEST['tab'] ?? '';
if(!in_array($cur_tab,$active_tabs)) {
  $cur_tab = $active_tabs[0];
}

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
      <p>If you switch tabs, you will your changes.</p>
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


