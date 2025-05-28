<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

function add_viewer_entry($section_prefix, $classes, $label_text, $hint_text) {
  $entry_key = explode(' ',$classes)[0];
  $cb_id = implode('-',[$section_prefix,$entry_key,'cb']);
  echo "<label class='$classes label' for='$cb_id'>$label_text:</label>";
  echo "<div class='$classes value'>";
  echo "  <div class='text'></div>";
  echo "  <input id='$cb_id' type='checkbox' class='hint-cb'></input>";
  echo "  <div class='hint'>$hint_text</div>";
  echo "</div>";
}

echo "<div id='editor-frame'>";
echo "  <div class='content-header'>Section/Question Details</div>";
echo "  <div class='hint-hint'>Click or hover on any of the entry labels for more info about that entry.</div>";


echo "<!--Section Editor-->";
echo "<div class='grid section editor'>";
echo "  Section Editor";
echo "</div>";


echo "<!--Section Viewer-->";
echo "<div class='grid section viewer'>";
add_viewer_entry('sv','name','Name',
  'Name used to identify this section.  If Show Name is YES, the section name will be included in the survey. Otherwise, it will only be used in the Admin Dashboard'
);
add_viewer_entry('sv','show-name','Show Name',
  'Whether or not to show the section name in the survey'
);
add_viewer_entry('sv','description','Description',
  '<b>This field is optional.</b>  If provided, it will be displayed in the survey before any of the questions or info text within this section.  The text can be stylized using markdown.'
);
add_viewer_entry('sv','feedback', 'Feedback',
  '<b>This field is optional.</b>  It sepecifies the label used to introduce a free text entry at the end of the section. If this field is empty, no such free text entry will be included in the survey for this section.'
);
echo "  </div>";


echo "  <!--Question Editor-->";
echo "  <div class='grid question editor'>";
echo "    Question Editor";
echo "  </div>";


echo "  <!--Question Viewer-->";
echo "  <div class='grid question viewer'>";
add_viewer_entry('qv','type','Type',
<<<HINT
Type of "question" entry in the survey. Possible values are:
  <p><b>Info Block</b> - Not actually a question.  This is a block of information included in the survey.</p>
  <p><b>Simple Checkbox</b> - For use with Yes/No questions.</p>
  <p><b>Single Selection</b> - Participant can no more than one option, if any</p>
  <p><b>Multiple Selections</b> - Participant can select as many options as apply</p>
  <p><b>Free Text</b> - A text box is provided for participant to provide a response in their own words.</p>
HINT
);
add_viewer_entry('qv','wording','Wording',
  'The actual wording of the question on the survey'
);
add_viewer_entry('qv','description','Description',
  "<b>This field is optional.</b>  If provided, this will appear in the survey to provide additional 
  information (background, context, whatever...) about the question.  The text can be 
  stylized using markdown.
  <p><a href='https://www.markdownguide.org/basic-syntax' target='_blank'>Markdown Reference</a></p>"
);
add_viewer_entry('qv','primary options','Primary Options',
  "For multiple choice questions, this is the primary list of options available to choose from.
   There must be at least one option provided for the question to be valid.  Ideally, there
   will be more than one option.  Otherwise, consider using a Simple Checkbox question."
);
add_viewer_entry('qv','secondary options','Secondary Options',
  "<b>This field is optional.</b>  Secondary options are only shown to the participant if they have 
  selected at least one of the primary options (assuming Javascript is enabled)."
);
add_viewer_entry('qv','other options','Other Options',
  "<b>This field is optional.</b>  This used for multiple choice questions where the participant
  should be allowed to fill in their own option.  If specified, this field provides the
  prompty that will appear in the survey to introduce the other option.  If not specified,
  the question will not include an input field for a participant provided option."
);
add_viewer_entry('qv','qualifier','Qualifier',
  "<b>This field is optional.</b>  This is used when it would be useful to allow the participant
  to provide additional information about their response.  If specified, this field provides
  the prompt that will appear in the survey to introduce the input field used to 
  qualify their response."
);
add_viewer_entry('qv','info','Info',
<<<INFO
  <div class='info-block'>The text of the information block.  
  The text can be stylized using markdown.
  <p><a href='https://www.markdownguide.org/basic-syntax' target='_blank'>Markdown Reference</a></p>
  </div>
  <div class='other-type'><b>This field is optional.</b>  If provided, it provides the text that
  will appear in the popup hint associated with the question input fields.
  </div>
INFO
);
echo "</div>";

echo "</div>";

