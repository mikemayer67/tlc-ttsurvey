<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));

$nonce = gen_nonce('admin-surveys');

// survey data
$all_surveys = all_surveys();

@todo("remove bogus test data");
foreach(['active','draft','closed'] as $status) {
  foreach(($all_surveys[$status]??[]) as $i => &$survey) {
    $sections = [
      [],
      [ 'name' => 'Section 1', 'description' => "some words about section 1", 'feedback' => 0 ],
      [ 'name' => 'Section Deux', 'description' => "But why is the name partially in Frenche?  That's a might fine question for which I do not have an answer.  Ok, reasonable,  .... but what are we even talking about at this point?", 'feedback' => 1 ],
      [ 'name' => 'Section 3', 'description' => "some words about section 3", 'feedback' => 0 ],
      [ 'name' => 'Section 4', 'description' => "some words about section 4", 'feedback' => 0 ],
      [ 'name' => 'Section 5', 'description' => "some words about section 5", 'feedback' => 0 ],
      [ 'name' => 'Section 6', 'description' => "some words about section 6", 'feedback' => 0 ],
      [ 'name' => 'Section 7', 'description' => "some words about section 7", 'feedback' => 0 ],
    ];
    $id = 1;
    foreach($sections as &$section) {
      $section['elements'] = [
        [
          'id' => ++$id, 
          'type' => 'INFO', 
          'label' => 'Info Text', 
          'info'=>'This is where the text goes.  Skipping markdown/HTML for now (**mostly**).  But am adding a some italics and *bold*.',
        ],
        [
          'id' => ++$id, 
          'type' => 'BOOL', 
          'label' => 'Yes/No Questions',
          'qualifier' => 'Why or why not?',
          'description' => 'Blah blah blah... This is important because',
          'info'=>'This is popup info.  Just here to see if popups are working',
        ],
        [
          'id' => ++$id, 
          'type' => 'OPTIONS', 
          'label' => 'Select Question #1',
          'multiple' => 0,
          'other' => 'Other',
          'qualifier' => 'Anything we should know?',
          'description' => "Pick whichever answer best applies.  Or provide your own if you don't like the options provided",
          'info'=>'This is popup info.  Just here to see if popups are working',
          'options' => [ [3, "Three"], [2, 'Two'], [1,"negative i^2"], ],
        ],
        [
          'id' => ++$id, 
          'type' => 'OPTIONS', 
          'label' => 'Select Question #2',
          'multiple' => 0,
          'qualifier' => 'Anything we should know?',
          'description' => 'Pick whichever answer best applies.',
          'info'=>'This is popup info.  Just here to see if popups are working',
          'options' => [ [3, "Three"], [2, 'Two'], [1,"negative i^2"], ],
        ],
        [
          'id' => ++$id, 
          'type' => 'OPTIONS', 
          'label' => 'Multi Select #1',
          'multiple' => 1,
          'other' => 'Other',
          'qualifier' => 'Anything we should know?',
          'description' => 'Pick whichever answer or answers best apply.  Provide your own if you think we missed something.',
          'info'=>'This is popup info.  Just here to see if popups are working',
          'options' => [ [3, "Three"], [2, 'Two'], [1,"negative i^2"], ],
        ],
        [
          'id' => ++$id, 
          'type' => 'OPTIONS', 
          'label' => 'Multi Select #2',
          'multiple' => 1,
          'qualifier' => 'Anything we should know?',
          'description' => 'Pick whichever answer or answers best apply.',
          'info'=>'This is popup info.  Just here to see if popups are working',
          'options' => [ [3, "Three"], [2, 'Two'], [1,"negative i^2"], ],
        ],
        [
          'id' => ++$id, 
          'type' => 'FREETEXT', 
          'label' => 'Your thoughts?',
          'description' => 'What else would you like us to know?',
          'info'=>'This is popup info.  Just here to see if popups are working',
        ],
      ];
    }
    $all_surveys[$status][$i]['sections'] = $sections;
  }
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
  <div id='survey-tree'></div>
  <div class='resizer'></div>
  <div id='element-editor'></div>
</div>

<!--Button Bar-->
</div>
<div class='button-bar'>
  <input id='changes-submit' class='submit' type='submit' value='Save Changes'>
  <input id='changes-revert' class='revert' type='submit' value='Revert' formnovalidate>
</div>

</form>
HTML;

echo "<script type='module' src='", js_uri('surveys','admin'), "'></script>";
//echo "<script src='", js_uri('survey_editor','admin'), "'></script>";
echo "<script src='", js_uri('dayjs.min'), "'></script>";
