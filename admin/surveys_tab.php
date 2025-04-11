<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));

$nonce = gen_nonce('admin-surveys');

$all_surveys = all_surveys();
$active = $all_surveys['active'];
$drafts = $all_surveys['drafts'];
$closed = $all_surveys['closed'];

$cur_survey = null;
$cur_status = null;
if($active) {
  $cur_survey = $active[0]['id'];
  $cur_status = 'active';
} elseif($drafts) {
  $cur_survey = $drafts[0]['id'];
  $cur_status = 'draft';
} elseif($closed) {
  $cur_survey = $closed[0]['id'];
  $cur_status = 'closed';
}

$form_uri = app_uri('admin');
echo "<form id='admin-surveys' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
add_hidden_submit('action','surveys');

echo <<<HTMLCTRLS
<div class='survey-controls'>
  <div class='survey-id'>
    <label>Survey
      <select id='survey-select' name='survey-d'>
HTMLCTRLS;

if($active) {
  echo "<option class='header' disabled>Active</option>";
  foreach($all_surveys['active'] as $survey) {
    $id    = $survey['id'];
    $title = $survey['title'];
    echo "<option class='active' status='active' value='$id' selected>$title</option>";
  }
}
echo "<option class='header' disabled>Drafts</option>";
echo "<option class='new' status='new' value='new'>New...</option>";
foreach($drafts as $survey) {
  $id    = $survey['id'];
  $title = $survey['title'];
  echo "<option class='draft' status='draft' value='$id'>$title</option>";
}
if($closed) {
  echo "<option class='header' disabled>Closed</option>";
  foreach($all_surveys['closed'] as $survey) {
    $id    = $survey['id'];
    $title = $survey['title'];
    echo "<option class='closed' status='closed' value='$id'>$title</option>";
  }
}

echo <<<HTMLCTRLS
      </select>
    </label>
  </div>
  <span class='survey-status'>$cur_status</span>
  <div class='survey-actions'>
HTMLCTRLS;

if(in_array('admin',$active_roles)) {
  $hidden = ($cur_status !== 'draft') ? 'hidden' : '';
  echo "<a class='action draft $hidden' target='active'>Go Live</a>";
  $hidden = ($cur_status !== 'active') ? 'hidden' : '';
  echo "<a class='action active $hidden' target='draft'>Return to Draft</a>";
  echo "<a class='action active add-sep $hidden' target='closed'>Close</a>";
  $hidden = ($cur_status !== 'closed') ? 'hidden' : '';
  echo "<a class='action closed $hidden' target='active'>Reopen</a>";
}

echo <<<HTMLCTRLS
  </span></div>
</div>

<div class='content-box'>
<pre>
STUFF WILL GO
HERE
</pre>
</div>
HTMLCTRLS;

$hidden = ($cur_status!=='active') ? 'hidden':'';
echo "<div class='button-bar $hidden'>";
echo "<input id='changes-submit' class='submit' type='submit' value='Save Changes'>";
echo "<input id='changes-revert' class='revert' type='submit' value='Revert' formnovalidate>";
echo "</div>";

echo "</form>";
echo "<script src='", js_uri('surveys','admin'), "'></script>";

die();

