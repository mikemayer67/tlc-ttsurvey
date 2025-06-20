<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));

$nonce = gen_nonce('admin-surveys');

// survey data
$all_surveys = all_surveys();

// add content data to the first survey that will be shown
if($all_surveys) {
  $id = $all_surveys[0]['id'];
  $all_surveys[0]['content'] = survey_content($id);
}

echo "<script>";
echo "const ttt_all_surveys = " . json_encode($all_surveys) . ";";
echo "</script>";

$form_uri = app_uri('admin');
echo "<form id='admin-surveys' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
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
<table id='info-edit' class='input-table left'>
  <tr class='survey-name'>
    <td class='label'>Survey Name:</td>
    <td class='input-box'>
      <input id='survey-name' type='input' class='alphanum-only watch' name='survey_name'>
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
        <select id='existing-pdf-action' name='pdf_action'>
          <option value='keep'>Keep it</option>
          <option value='drop'>Drop it</option>
          <option value='replace'>Replace it</option>
        </select>
        <button id='clear-pdf'>-</button>
        <input id='survey-pdf' type='file' class='watch' name='survey_pdf' accept='.pdf'>
      </div>
    </td>
  </tr>
</table>

<!--Survey Content-->
<div id='content-editor'>

  <div class='menubar'>
    <button class='up' title='Move selection up'><span class=icon></span></button>
    <button class='down' title='Move selection down'><span class=icon></span></button>
    <button class='add section below' title='Insert new section below'><span class=icon></span></button>
    <button class='add section above' title='Insert new section above'><span class=icon></span></button>
    <button class='add question below' title='Insert new survey item below'><span class=icon></span></button>
    <button class='add question above' title='Insert new survey item above'><span class=icon></span></button>
    <button class='add question clone' title='Duplicate survey item'><span class=icon></span></button>
    <button class='delete' title='Delete selection'><span class=icon></span></button>
    <button class='undo' title='Undo edit'><span class=icon></span></button>
    <button class='redo' title='Redo edit'><span class=icon></span></button>
  </div>
  
  <div class='body'>
    <div id='survey-tree'>
      <div class='content-header'>Structure</div>
      <div class='info'>Drag to Reorder</div>
      <ul class='sections'></ul>
    </div>
    <div class='resizer'></div>
HTML;
require(app_file('admin/survey_frame.php'));
echo <<<HTML
  </div>

</div>

<!--Button Bar-->
</div>
<div class='submit-bar'>
  <input id='changes-submit' class='submit' type='submit' value='Save Changes'>
  <input id='changes-revert' class='revert' type='submit' value='Revert' formnovalidate>
</div>

</form>
HTML;

echo "<script type='module' src='", js_uri('surveys','admin'), "'></script>";
//echo "<script src='", js_uri('survey_editor','admin'), "'></script>";
echo "<script src='", js_uri('dayjs.min'), "'></script>";
