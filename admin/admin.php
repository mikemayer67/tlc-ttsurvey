<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/elements.php'));
require_once(app_file('admin/elements.php'));

log_dev("-------------- Start of Admin Dashboard --------------");

log_dev("POST = ".print_r($_POST,true));

$admin_id = $_SESSION['admin-id'] ?? null;
if( $admin_id && ($user = User::lookup($admin_id)) ) {
  if(!verify_role($admin_id,'admin')) { 
    log_warning("Invalid admin login attempt with admin id: $admin_id");
    $admin_id = '';
    unset($_SESSION['admin-id']);
  }
}

if(!$admin_id) {
  require(app_file('login/admin.php'));
  die();
}

$cur_tab = $_REQUEST['tab'] ?? 'settings';
$tab_css = css_uri($cur_tab,'admin');

start_page('admin',
  [ 'css'=>css_uri($cur_tab,'admin'), ]
);

$form_uri = app_uri('admin');
$nonce = gen_nonce('admin-navbar');

$tabs = [
  'settings' => [],
  'roles' => ['admin'],
  'log' => ['admin','tech'],
];

echo "<!-- Admin Tabs -->";
echo "<form id='admin-tabs' class='admin-navbar' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);

echo "<div class='tabs'>";
foreach($tabs as $tab=>$access)
{
  $disabled = ($cur_tab === $tab) ? "disabled class='active'" : '';
  echo "<button $disabled name='tab' value='$tab'>$tab</button>";
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


