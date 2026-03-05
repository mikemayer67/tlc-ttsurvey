<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

$nonce = gen_nonce('admin-roles');

require_once(app_file('admin/elements.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/settings.php'));
require_once(app_file('include/sort_keys.php'));

$users = array();
foreach(User::all_users() as $user) {
  $userid = $user->userid();
  $name   = $user->fullname();

  $users[$userid] = [
    'name'=>$name, 
    'sort_key'=>surname_sort_key($name),
  ];
}
// sort and then strip the sort key from the users array
uasort($users, fn($a,$b) => $a['sort_key'] <=> $b['sort_key']);
array_walk($users, function(&$a) { $a = $a['name']; });


$form_uri = app_uri('admin');
echo "<form id='admin-roles' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
add_hidden_submit('action','roles');

echo "<div class='column-wrapper'>";

// Admin Roles
echo "<div class='admin roles'>";

echo "<ul>";
$primary_admin = primary_admin();
echo "<li class='role'>Primary Admin</li><ul>";

add_admin_info_text(
  'primary-admin',
  'Can modify the survey app settings, edit user roles, plus anything any survey admin can do.'
);

echo "<li><select id='primary-admin-select' name='primary-admin'>";
echo "<option value=''>--nobody--</option>";
foreach($users as $userid=>$name) {
  $selected = ($userid===$primary_admin ? 'selected' : '');
  echo "<option value='$userid' $selected>$name</option>";
}
echo "</select></li>";
echo "</ul></li>";


$survey_admins = survey_admins();
echo "<li class='role'>Survey Admins</li>";
add_admin_select(
  'admin',$users,survey_admins(),
  'Can create, monitor, and change status of surveys, assign editor roles, plus anything an editor can do.'
);

echo "<li class='role'>Content Editors</li>";
add_admin_select(
  'content',$users,content_admins(),
  'Can modify the structure and content of surveys that are in draft status.'
);

echo "<li class='role'>Technical Contacts</li>";
add_admin_select(
  'tech',$users,tech_admins(),
  'These are the folks to contact if something is not working correctly.'
);

echo "</ul>";

// Summary Roles
$summary_flags = (int)get_setting('summary_flags');
echo "</div><div class='summary roles'>";
echo "  <ul>";
echo "    <li class='role'>Summary Access</li>";
echo "    <ul>";
$checked = ($summary_flags & 1) ? 'checked' : '';
echo "      <li class='public-summary'>";
echo "        <label>";
echo "          <input class='summary flag public' type='checkbox' value='1' $checked>";
echo "          <span>All survey participants</span>";
echo "        </label>";
echo "      </li>";
$checked = ($summary_flags & 2) ? 'checked' : '';
echo "      <li class='require-submission'>";
echo "        <label>";
echo "          <input class='summary flag submitted' type='checkbox' value='2' $checked>";
echo "          <span>Require survey submission</span>";
echo "        </label>";
echo "      </li>";
echo "      <li class='participant-list-header'>Grant Access To:</li>";
echo "      <li>";
echo "        <div class='participant-list'>";
foreach( $users as $userid=>$name ) {
  $roles = assigned_roles($userid);
  $checked = in_array('summary',$roles) ? 'checked' : '';
  $admin   = in_array('admin',$roles) || in_array('primary-admin',$roles) ? '(*)' : '';
  echo "<label>";
  echo "  <input class='summary access' type='checkbox' userid='$userid' $checked>";
  echo "  <span>$name$admin</span>";
  echo "</label>";
}
echo "        </div>";
echo "      </li>";
echo "    </ul>";
echo "  </ul>";
echo "</div>";

echo "</div>";

echo "<div class='submit-bar'>";
echo "<input id='changes-submit' class='submit' type='submit' value='Save Changes'>";
echo "<input id='changes-revert' class='revert' type='submit' value='Revert' formnovalidate>";
echo "</div>";

echo "</form>";

echo "<script src='", js_uri('roles','admin'), "'></script>";
