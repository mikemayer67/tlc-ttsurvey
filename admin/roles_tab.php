<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

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
echo "<li><select id='primary-admin-select' name='primary-admin'>";
echo "<option value=''>--nobody--</option>";
foreach($users as $userid=>$name) {
  $selected = ($userid===$primary_admin ? 'selected' : '');
  echo "<option value='$userid' $selected>$name</option>";
}
echo "</select></li>";
echo "</ul></li>";


$survey_admins = survey_admins();
echo "<li class='role'>Survey Admins</li><ul>";
foreach($survey_admins as $userid) {
  $name = $users[$userid];
  echo "<li class='user'>";
  echo "<span class='name'>$name</span>";
  echo "<button class='remove' userid='$userid' from='admin'>-</button>";
  echo "</li>";
}
echo "<li class='new user'>";
echo "<input type=text id='new-admin' placeholder='Add Another...'>";
echo "<ul id='new-admin-list' class='user-list'><li>Test</li><li>Me</li></ul>";
echo "</ul></li>";

$content_admins = content_admins();
echo "<li class='role'>Content Editors</li><ul>";
foreach($content_admins as $userid) {
  $name = $users[$userid];
  echo "<li class='user'>";
  echo "<span class='name'>$name</span>";
  echo "<button class='remove' userid='$userid from='content''>-</button>";
  echo "</li>";
}
echo "</ul></li>";

$tech_admins = tech_admins();
echo "<li class='role'>Technical Contacts</li><ul>";
foreach($tech_admins as $userid) {
  $name = $users[$userid];
  echo "<li class='user'>";
  echo "<span class='name'>$name</span>";
  echo "<button class='remove' userid='$userid from='tech''>-</button>";
  echo "</li>";
}
echo "</ul></li>";



echo "</ul>";

 

echo "<div class='button-bar'>";
echo "<input id='settings_submit' class='submit' type='submit' value='Save Changes'>";
echo "</div>";

echo "</form>";

$js_uri = resource_uri('admin/js/roles.js');
echo "<script src='$js_uri'></script>";
