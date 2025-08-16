<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

$nonce = gen_nonce('admin-roles');

require_once(app_file('admin/elements.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/settings.php'));

$users = array();
foreach(User::all_users() as $user) {
  $userid = $user->userid();
  $name   = $user->fullname();
  $users[$userid] = $name;
}

$form_uri = app_uri('admin');
echo "<form id='admin-roles' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
add_hidden_submit('action','roles');

echo "<ul>";

$primary_admin = primary_admin();
echo "<li class='role'>Primary Admin</li><ul>";
add_admin_info_text('primary-admin');
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
add_admin_select('admin',$users,survey_admins());

echo "<li class='role'>Content Editors</li>";
add_admin_select('content',$users,content_admins());

echo "<li class='role'>Technical Contacts</li>";
add_admin_select('tech',$users,tech_admins());

echo "</ul>";

 

echo "<div class='submit-bar'>";
echo "<input id='changes-submit' class='submit' type='submit' value='Save Changes'>";
echo "<input id='changes-revert' class='revert' type='submit' value='Revert' formnovalidate>";
echo "</div>";

echo "</form>";

echo "<script src='", js_uri('roles','admin'), "'></script>";
