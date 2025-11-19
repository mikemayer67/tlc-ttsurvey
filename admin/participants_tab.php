<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

$nonce = gen_nonce('admin-participants');

require_once(app_file('admin/elements.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/timestamps.php'));

$active_survey_id    = active_survey_id();
$active_survey_title = active_survey_title() ?? "No active survey";


$form_uri = app_uri('admin');
echo "<form id='admin-participants' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
add_hidden_submit('action','roles');

$inactive = $active_survey_id ? '' : 'inactive';

$user_status = [];
if($active_survey_id) {
  $query = <<<SQL
    SELECT userid,
           UNIX_TIMESTAMP(draft)      as draft,
           UNIX_TIMESTAMP(submitted)  as submitted
      FROM tlc_tt_user_status 
     WHERE survey_id=(?)
  SQL;

  $rows = MySQLSelectRows($query,'i',$active_survey_id);
  foreach($rows as $row) {
    $user_status[$row['userid']] = ['draft'=>$row['draft'], 'submitted'=>$row['submitted']];
  }
}

$last_survey = [];
$rows = MySQLSelectArrays('SELECT userid,survey_id,survey_name from tlc_tt_view_last_user_survey','');
foreach($rows as $row) { $last_survey[$row[0]] = ['id'=>$row[1], 'name'=>$row[2]]; }

echo "<table id='participants'>";
echo "  <thead><tr>";
echo "    <th rowspan='2' class='select'>(select)</th>";
echo "    <th rowspan='2' class='userid' data-sort='userid'>User ID</th>";
echo "    <th rowspan='2' class='fullname' data-sort='fullname'>Name</th>";
echo "    <th rowspan='2' class='email' data-sort='email'>Email</th>";
echo "    <th rowspan='2' class='pwreset'></th>";
echo "    <th colspan='2' class='active-survey $inactive'>$active_survey_title</th>";
echo "    <th rowspan='2' class='last-survey' data-sort='last-survey'>Last Participation</th>";
echo "  </tr><tr>";
echo "    <th class='draft $inactive' data-sort='draft'>Draft</th>";
echo "    <th class='submitted $inactive' data-sort='submitted'>Submitted</th>";
echo "  </tr></thead>";
echo "  <tbody>";

foreach(User::all_users() as $user) {
  $userid  = $user->userid();
  $fullname = $user->fullname();
  $email   = $user->email() ?? ''; 

  $name_parts = explode(' ',$fullname);
  array_unshift($name_parts, array_pop($name_parts));
  $name_parts = strtolower(implode(' ',$name_parts));

  $draft_ts     = $user_status[$userid]['draft']     ?? 0;
  $submitted_ts = $user_status[$userid]['submitted'] ?? 0;

  $draft_str     = $draft_ts     ? timestamp_string($draft_ts,     'j-M-y g:ia') : '';
  $submitted_str = $submitted_ts ? timestamp_string($submitted_ts, 'j-M-y g:ia') : '';

  $last_id   = $last_survey[$userid]['id']   ?? 0;
  $last_name = $last_survey[$userid]['name'] ?? '';

  echo "<tr>";
  echo "<td class='select'><input type='checkbox' name='selected_users' value='$userid'></td>";
  echo "<td class='userid' data-sort-value='$userid'>$userid</td>";
  echo "<td class='fullname' data-sort-value='$name_parts'>$fullname</td>";
  echo "<td class='email' data-sort-value='$email'>$email</td>";
  echo "<td class='pwreset'><button class='pwreset' data-userid='$userid'>reset password</button></td>";
  echo "<td class='draft' data-sort-value='$draft_ts'>$draft_str</td>";
  echo "<td class='submitted' data-sort-value='$submitted_ts'>$submitted_str</td>";
  echo "<td class='last-survey' data-sort-value='$last_id'>$last_name</td>";
  echo "</tr>";
}

echo "  </tbody>";
echo "</table>";


echo "</form>";

echo "<script src='", js_uri('participants','admin'), "'></script>";
