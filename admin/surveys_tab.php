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
add_hidden_input('pdfuri',full_app_uri("admin&ttt=$nonce&pdf="));
add_hidden_submit('action','surveys');

echo <<<HTMLCTRLS
<div class='survey-controls'>
  <div class='survey-id'>
    <select id='survey-select' name='survey-id'></select>
  </div>
  <label>Status:<span class='survey-status'></span></label>
  <div class='survey-actions'>
HTMLCTRLS;

if(in_array('admin',$active_roles)) {
  echo "<a class='action draft' target='active'>Go Live</a>";
  echo "<a class='action active' target='draft'>Edit</a>";
  echo "<a class='action active add-sep' target='closed'>Close</a>";
  echo "<a class='action closed' target='active'>Reopen</a>";
}

echo <<<HTML
  </div>
</div>

<div class='content-box'>

<!--Info Bar-->
<div class='info-bar'>
  <label class='info-label created'>Created:<span class='date'>???</span></label>
  <label class='info-label opened'>Opened:<span class='date'>???</span></label>
  <label class='info-label closed'>Closed:<span class='date'>???</span></label>
  <span class='pdf-link'>
    <span class='no-link'>No PDF</span>
    <a class='pdf-download' download>Download PDF</a>
  </span>
</div>

<!--New Survey Table-->
<table id='info-edit' class='input-table new-survey'>
  <tr class='survey-name'>
    <td class='label'>Survey Name:</td>
    <td class='input-box'>
      <input id='survey-name' type='input' class='alphanum-only' name='survey_name'>
      <div class='error' name='survey_name'>error</div>
    </td>
  </tr><tr class='clone-from'>
    <td class='label'>Clone From:</td>
    <td><select id='survey-clone-from'>
      <option class='none' status='none' value='none' selected>--None--</option>
    </select></td>
  </tr><tr class='pdf-file'>
    <td class='label'>Downloadable PDF:</td>
    <td>
      <div class='pdf-box'>
        <select id='existing-pdf-action'>
          <option value='keep'>Keep it</option>
          <option value='drop'>Drop it</option>
          <option value='replace'>Replace it</option>
        </select>
        <button class='clear-pdf'>-</button>
        <input id='survey-pdf' type='file' name='survey_pdf' accept='.pdf'>
      </div>
    </td>
  </tr>
</table>

<!--Button Bar-->
</div>
<div class='button-bar'>
  <input id='changes-submit' class='submit' type='submit' value='Save Changes'>
  <input id='changes-revert' class='revert' type='submit' value='Revert' formnovalidate>
</div>

</form>
HTML;

echo "<script src='", js_uri('surveys','admin'), "'></script>";
echo "<script src='", js_uri('dayjs.min'), "'></script>";

die();

