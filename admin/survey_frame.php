<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

$hints = [
  'section' => [
    'name' => ( 
      'Name used to identify this section.  If Show Name is YES, the section name will be included in the '.
      'survey. Otherwise, it will only be used in the Admin Dashboard'),
    'show-name' => ( 
      'Whether or not to show the section name in the survey'),
    'description' => ( 
      '<b>This field is optional.</b>  If provided, it will be displayed in the survey before '.
      'any of the questions or info text within this section.  The text can be stylized using markdown. '.
      "p><a href='https://www.markdownguide.org/basic-syntax' target='_blank'>Markdown Reference</a></p>"),
    'feedback' => ( 
      '<b>This field is optional.</b>  It sepecifies the label used to introduce a free text entry at the '.
      'end of the section. If this field is empty, no such free text entry will be included in the survey '.
      'for this section.'
    ),
  ],
  'question' => [
    'type' => (
       'Type of "question" entry in the survey. Possible values are: '.
       '<p><b>Info Block</b> - Not actually a question.  This is a block of information included in the survey.</p> '.
       '<p><b>Simple Checkbox</b> - For use with Yes/No questions.</p> '.
       '<p><b>Single Selection</b> - Participant can select no more than one option</p> '.
       '<p><b>Multiple Selections</b> - Participant can select as many options as apply</p> '.
       '<p><b>Free Text</b> - A text box is provided for participant to provide a response in their own words.</p> '),
    'wording' => (
      'The actual wording of the question on the survey'),
    'description' => (
      '<b>This field is optional.</b>  If provided, this will appear in the survey to provide additional '.
      'information (background, context, whatever...) about the question.  The text can be '.
      'stylized using markdown. '.
      "<p><a href='https://www.markdownguide.org/basic-syntax' target='_blank'>Markdown Reference</a></p>" ),
    'primary' => (
      'For multiple choice questions, this is the primary list of options available to choose from. '.
      'There must be at least one option provided for the question to be valid.  Ideally, there '.
      'will be more than one option.  Otherwise, consider using a Simple Checkbox question.' ),
    'secondary' => (
      '<b>This field is optional.</b>  Secondary options are only shown to the participant if they have  '.
      'selected at least one of the primary options (assuming Javascript is enabled).'),
    'other' => (
      '<b>This field is optional.</b>  This used for multiple choice questions where the participant '.
      'should be allowed to fill in their own option.  If specified, this field provides the '.
      'prompty that will appear in the survey to introduce the other option.  If not specified, '.
      'the question will not include an input field for a participant provided option.'),
    'qualifier' => (
      '<b>This field is optional.</b>  This is used when it would be useful to allow the participant '.
      'to provide additional information about their response.  If specified, this field provides '.
      'the prompt that will appear in the survey to introduce the input field used to '.
      'qualify their response.'),
    'info' => (
      "<div class='info-block'>The text of the information block. ".
      "The text can be stylized using markdown. ".
      "<p><a href='https://www.markdownguide.org/basic-syntax' target='_blank'>Markdown Reference</a></p> ".
      "</div> ".
      "<div class='other-type'><b>This field is optional.</b>  If provided, it provides the text that ".
      "will appear in the popup hint associated with the question input fields. ".
      "</div> "),
  ],
];

$labels = [
  'section' => [
    'name'        => 'Name',
    'show-name'   => 'Show Name',
    'description' => 'Description',
    'feedback'    => 'Feedback',
  ],
  'question' => [
    'type'        => 'Type',
    'wording'     => 'Wording',
    'description' => 'Description',
    'primary'     => 'Primary Options',
    'secondary'   => 'Seconday Options',
    'other'       => 'Other Option',
    'qualifier'   => 'Qualifier',
    'info'        => 'Info',
  ],
];

function add_viewer_entry($scope, $key, $extra_classes='') 
{
  global $hints;
  global $labels;

  $label = $labels[$scope][$key];
  $hint = $hints[$scope][$key];
  echo "<div class='$key $extra_classes label'><span>$label:</span></div>";
  echo "<div class='$key $extra_classes value'>";
  echo "  <div class='text'></div>";
  echo "  <div class='hint'>$hint</div>";
  echo "</div>";
}

function add_editor_input($scope, $key, $kwargs=[])
{
  global $hints;
  global $labels;

  $label = $labels[$scope][$key];
  $hint = $hints[$scope][$key];
  $extra = $kwargs['extra_classes'] ?? '';
  $required = $kwargs['required'] ?? false;
  $placeholder = $required ? '[required]' : '[optional]';
  $name = "$scope-$key";

  echo "<div class='$key $extra label'><span>$label:</span></div>";
  echo "<div class='$key $extra value'>";
  echo "  <input class='$scope $key' name='$name' placeholder='$placeholder'></input>";
  echo "  <div class='hint'>$hint</div>";
  echo "</div>";
}

function add_editor_textarea($scope, $key, $kwargs=[])
{
  global $hints;
  global $labels;

  $label = $labels[$scope][$key];
  $hint = $hints[$scope][$key];

  $extra = $kwargs['extra_classes'] ?? '';
  $required = $kwargs['required'] ?? false;
  $maxlen = $kwargs['maxlen'] ?? 1024;

  $placeholder = $required ? '[required]' : '[optional]';
  $name = "$scope-$key";

  echo "<div class='$key $extra label'><span>$label:</span></div>";
  echo "<div class='$key $extra value'>";
  echo "  <div class='textarea-wrapper'>";
  echo "    <textarea class='$scope $key' name=$name placeholder='$placeholder' maxlength='$maxlen'></textarea>";
  echo "    <div class='char-count'><span class='cur'>0</span>/$maxlen</div>";
  echo "  </div>";
  echo "  <div class='hint'>$hint</div>";
  echo "</div>";
}

function add_editor_select($scope, $key, $options, $kwargs=[])
{
  global $hints;
  global $labels;

  $label = $labels[$scope][$key];
  $hint = $hints[$scope][$key];

  $extra = $kwargs['extra_classes'] ?? '';

  $name = "$scope-$key";

  echo "<div class='$key $extra label'><span>$label:</span></div>";
  echo "<div class='$key $extra value'>";
  echo "  <select class='$scope $key' name=$name>";
  foreach($options as $option) {
    echo "    <option value=$option>$option</option>";
  }
  echo "  </select>";
  echo "  <div class='hint'>$hint</div>";
  echo "</div>";
}


echo "<div id='editor-frame'>";
echo "  <div class='content-header'>Section/Question Details</div>";
echo "  <div class='hint-hint'>Click or hover on any of the entry labels for more info about that entry.</div>";


echo "<!--Section Editor-->";
echo "<div class='grid section editor'>";
add_editor_input('section','name',['required'=>true]);
add_editor_select('section','show-name',["YES","NO"]);
add_editor_textarea('section','description',['maxlen'=>512]);
add_editor_input('section','feedback');
echo "</div>";


echo "<!--Section Viewer-->";
echo "<div class='grid section viewer'>";
add_viewer_entry('section','name');
add_viewer_entry('section','show-name');
add_viewer_entry('section','description');
add_viewer_entry('section','feedback');
echo "  </div>";


echo "  <!--Question Editor-->";
echo "  <div class='grid question editor'>";
echo "    Question Editor";
echo "  </div>";


echo "  <!--Question Viewer-->";
echo "  <div class='grid question viewer'>";
add_viewer_entry('question','type');
add_viewer_entry('question','wording');
add_viewer_entry('question','description');
add_viewer_entry('question','primary','options');
add_viewer_entry('question','secondary','options');
add_viewer_entry('question','other','options');
add_viewer_entry('question','qualifier');
add_viewer_entry('question','info');
echo "</div>";

echo "</div>";

