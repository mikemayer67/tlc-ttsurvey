<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));

$nonce = gen_nonce('admin-surveys');

$all_surveys = all_surveys();

$form_uri = app_uri('admin');
echo "<form id='admin-surveys' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
add_hidden_input('surveys',json_encode($all_surveys));
add_hidden_submit('action','surveys');

echo <<<HTMLCTRLS
<div class='survey-controls'>
  <div class='survey-id'>
    <label>Survey
      <select id='survey-select' name='survey-d'></select>
    </label>
  </div>
  <span class='survey-status'></span>
  <div class='survey-actions'>
HTMLCTRLS;

if(in_array('admin',$active_roles)) {
  echo "<a class='action draft' target='active'>Go Live</a>";
  echo "<a class='action active' target='draft'>Return to Draft</a>";
  echo "<a class='action active add-sep' target='closed'>Close</a>";
  echo "<a class='action closed' target='active'>Reopen</a>";
}

echo <<<HTML
  </div>
</div>

<div class='content-box'>

<!--New Survey Table-->
<table id='new-survey' class='input-table new-survey'>
  <tr class='survey-name'>
    <td class='label'>Survey Name:</td>
    <td><input id='new-survey-name' type='input' class='alphanum-only' name='survey_name' placeholder='required' required></td>
  </tr><tr class='clone-from'>
    <td class='label'>Clone From:</td>
    <td><select id='new-survey-clone'>
      <option class='none' status='none' value='none' selected>--None--</option>
    </select></td>
  </tr><tr class='pdf-file'>
    <td class='label'>Downloadable PDF:</td>
    <td><input id='new-survey-pdf' type='file' name='new_survey_pdf' accept='.pdf'></td>
  </tr>
</table>
</div>

<!--Survey Display or Edit Table -->


<!--Button Bar-->

<div class='button-bar'>
  <input id='changes-submit' class='submit' type='submit' value='Save Changes'>
  <input id='changes-revert' class='revert' type='submit' value='Revert' formnovalidate>
</div>

</form>
HTML;

echo "<script src='", js_uri('surveys','admin'), "'></script>";

die();

