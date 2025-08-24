<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

$type_labels = [
  'INFO' => 'Info Block',
  'BOOL' => 'Simple Checkbox',
  'SELECT_ONE' => 'Single Selection',
  'SELECT_MULTI' => 'Multiple Selections',
  'FREETEXT' => 'Free Text',
];

echo "<script>\n const typeLabels = " . json_encode($type_labels) . ";\n</script>\n";

$hints = [
  'section' => [
    'name' => ( 
      'Name used to identify this section.  If Collapsible is YES, the section name will be included in the '.
      'survey. Otherwise, it will only be used in the Admin Dashboard'),
    'collapsible' => ( 
      'Whether or not this section will be able to be collapsed (temporarily hidden) in the survey. '.
      ' Note that if the section is not collapsible, the section name will not be displayed.'),
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
    'archive' => (
      'Adds an existing question into the survey.  This option should be used whenever possible as it '.
      'maintains continuity between this survey and the survey(s) from which it was cloned.'
    ),
    'type' => (
       'Type of "question" entry in the survey. Possible values are: '.
       '<p><b>'.$type_labels['INFO'].'</b> - Not actually a question.  This is a block of information included in the survey.</p>'.
       '<p><b>'.$type_labels['BOOL'].'</b> - For use with Yes/No questions.</p>'.
       '<p><b>'.$type_labels['SELECT_ONE'].'</b> - Participant can select no more than one option</p>'.
       '<p><b>'.$type_labels['SELECT_MULTI'].'</b> - Participant can select as many options as apply</p>'.
       '<p><b>'.$type_labels['FREETEXT'].'</b> - A text box is provided for participant to provide a response in their own words.</p>'),
    'infotag' => (
      '<b>This field is optional.</b> If provided, it will be shown in the structure layout tree (&larr;) '.
      'rather than a truncated version of the information text.'.
      '<p>This label will not appear anywhere in the survey itself. It is for use in the Admin Dashboard only</p>'),
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
      'will be more than one option.  Otherwise, consider using a Simple Checkbox question.'.
      '<p class="editor-only"><b>Click</b> in the box above to get a list of available options</p>'.
      '<p class="editor-only"><b>Drag/Drop</b> options to reorder or to move between primary/seconday option lists</p>'.
      '<p class="editor-only"><b>Click</b> on the <b>x</b> next any given option to remove it from the list</p>'
    ),
    'secondary' => (
      '<b>This field is optional.</b>  Secondary options are only shown to the participant if they have  '.
      'selected at least one of the primary options (assuming Javascript is enabled).'.
      '<p class="editor-only"><b>Click</b> in the box above to get a list of available options</p>'.
      '<p class="editor-only"><b>Drag/Drop</b> options to reorder or to move between primary/seconday option lists</p>'.
      '<p class="editor-only"><b>Click</b> on the <b>x</b> next any given option to remove it from the list</p>'
    ),
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
      'The text of the information block.  The text can be stylized using markdown.'.
      "<p><a href='https://www.markdownguide.org/basic-syntax' target='_blank'>Markdown Reference</a></p>"),
    'popup' => (
      '<b>This field is optional.</b>  If provided, it provides the text that '.
      'will appear in the popup hint associated with the question input fields. '),
  ],
];

$labels = [
  'section' => [
    'name'        => 'Name',
    'collapsible' => 'Collapsible',
    'description' => 'Description',
    'feedback'    => 'Feedback',
  ],
  'question' => [
    'archive'     => 'Archive',
    'type'        => 'Type',
    'infotag'     => 'Info Tag',
    'wording'     => 'Wording',
    'description' => 'Description',
    'primary'     => 'Primary Options',
    'secondary'   => 'Seconday Options',
    'other'       => 'Other Option',
    'qualifier'   => 'Qualifier',
    'info'        => 'Info',
    'popup'       => 'Popup Hint',
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

  $attributes = "name='$name' data-key='$key' placeholder='$placeholder'";

  $maxlen = $kwargs['maxlen'] ?? 0;
  if($maxlen) { $attributes .= " maxlength='$maxlen'"; }

  echo "<div class='$key $extra label'><span>$label:</span></div>";
  echo "<div class='$key $extra value'>";
  echo "  <div class='wrapper'>";
  echo "    <input class='$scope $key' $attributes></input>";
  echo "    <span class='error'></span>";
  echo "  </div>";
  echo "  <div class='hint'>$hint</div>";
  echo "</div>";
}

function add_editor_textarea($scope, $key, $kwargs=[])
{
  global $hints;
  global $labels;

  $label = $labels[$scope][$key];
  $hint = $hints[$scope][$key];

  $extra    = $kwargs['extra_classes'] ?? '';
  $required = $kwargs['required'] ?? false;
  $maxlen   = $kwargs['maxlen'] ?? 1024;

  $placeholder = $required ? '[required]' : '[optional]';
  $name = "$scope-$key";

  $class = "$scope $key";
  $attr = "name='$name' data-key='$key' placeholder='$placeholder' maxlength='$maxlen'";

  if($kwargs['autoresize'] ?? false) {
    $attr  .= " rows='1'";
    $class .= " auto-resize";
  }

  echo "<div class='$key $extra label'><span>$label:</span></div>";
  echo "<div class='$key $extra value'>";
  echo "  <div class='textarea-wrapper'>";
  echo "    <textarea class='$class' $attr></textarea>";
  echo "    <div class='char-count'><span class='cur'>0</span>/<span class='max'>$maxlen</span></div>";
  echo "    <span class='error'></span>";
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
  echo "  <select class='$scope $key' name='$name' data-key='$key'>";
  foreach($options as [$value,$label]) {
    echo "    <option value=$value>$label</option>";
  }
  echo "  </select>";
  echo "  <div class='hint'>$hint</div>";
  echo "</div>";
}

function add_archive_select()
{
  global $hints;
  global $labels;

  $label = $labels['question']['archive'];
  $hint  = $hints['question']['archive'];

  echo "<div class='archive label'><span>$label:</span></div>";
  echo "<div class='archive value'>";
  echo "  <select class='question archive' name='question-archive' data-key='archive'>";
  echo "    <option value=''>Select Question...</option>";
  echo "  </select>";
  echo "  <div class='hint'>$hint</div>";
  echo "</div>";
}

function add_type_select()
{
  global $hints;
  global $labels;
  global $type_labels;

  $label = $labels['question']['type'];
  $hint  = $hints['question']['type'];

  echo "<div class='type label'><span>$label:</span></div>";
  echo "<div class='type value'>";
  echo "  <div class='type-wrapper'>";
  echo "    <div class='text'></div>";
  echo "    <select class='question type' name='question-type' data-key='type'>";
  echo "      <option value=''>Required...</option>";
  foreach($type_labels as $key => $value) {
    echo "    <option value='$key'>$value</option>";
  }
  echo "    </select>";
  echo "  </div>";
  echo "  <div class='hint'>$hint</div>";
  echo "</div>";
}

function add_option_entry($key,$kwargs=[])
{
  global $hints;
  global $labels;

  $label = $labels['question'][$key];
  $hint  = $hints['question'][$key];

  $tight = ($kwargs['tight'] ?? false) ? 'tight' : '';

  echo "<div class='$key options label $tight'><span>$label:</span></div>";
  echo "<div class='$key options value $tight'>";
  echo "  <div class='$key option selected'></div>";
  echo "  <span class='$key error'></span>";
  echo "  <div class='hint'>$hint</div>";
  echo "</div>";
}

function add_option_pool($kwargs=[])
{
  global $labels;

  $tight = ($kwargs['tight'] ?? false) ? 'tight' : '';

  echo "<div class='option pool label $tight'></div>"; // needed for flex alignment
  echo "<div class='option pool $tight'>";
  echo "  <button class='add option' type='button'>+</button>";
  echo "</div>";
}

// 
// Start of the actual html generation
//


echo "<div id='editor-frame'>";
echo "  <div class='content-header'>Section/Question Details</div>";
echo "  <div class='hint-hint'>Click or hover on any of the entry labels for more info about that entry.</div>";


echo "<!--Section Editor-->";
echo "<div class='grid section editor'>";
add_editor_input('section','name',['required'=>true, 'maxlen'=>128]);
add_editor_select('section','collapsible',[[1,"YES"],[0,"NO"]]);
add_editor_textarea('section','description',['maxlen'=>512]);
add_editor_input('section','feedback',['maxlen'=>128]);
echo "</div>";


echo "<!--Section Viewer-->";
echo "<div class='grid section viewer'>";
add_viewer_entry('section','name');
add_viewer_entry('section','collapsible');
add_viewer_entry('section','description');
add_viewer_entry('section','feedback');
echo "  </div>";


echo "  <!--Question Editor-->";
echo "  <div class='grid question editor'>";
add_type_select();
echo "<div class='archive or'><span>or</span></div>";
echo "<div class='archive'><span></span></div>";
add_archive_select();
add_editor_input('question','infotag',['maxlen'=>128]);
add_editor_input('question','wording',['required'=>true, 'maxlen'=>128]);
add_editor_textarea('question','description',['maxlen'=>'512']);
add_option_entry('primary');
add_option_entry('secondary',['tight'=>true]);
add_option_pool(['tight'=>true]);
add_editor_input('question','other',['extra_classes'=>'options','maxlen'=>45]);
add_editor_input('question','qualifier',['maxlen'=>45]);
add_editor_textarea('question','popup',['maxlen'=>128, 'autoresize'=>true]);
add_editor_textarea('question','info',['required'=>true,'maxlen'=>1024]);
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
add_viewer_entry('question','popup');
add_viewer_entry('question','info');
echo "</div>";

echo "</div>";

