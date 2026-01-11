<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Render --------------");

handle_warnings();

require_once(app_file('include/logger.php'));
require_once(app_file('survey/markdown.php'));

class RenderEngine 
{
  private $popup_icon = null;

  private $in_box       = false;
  private $in_grid      = false;
  private $follows_info = false;

  function __construct()
  {
    $this->popup_icon = "<img class='popup' src='" . img_uri('icons8/info.png') . "'></img>";
  }

  public function render($state, $content, $kwargs)
  {
    $this->in_box       = false;
    $this->in_grid      = false;
    $this->follows_info = false;

    $responses = $kwargs['responses'] ?? [];
    $feedback  = $kwargs['feedback']  ?? [];

    if($state === 'preview')
    {
      $action = null;
      echo "<form id='survey'>";
    } 
    else {
      $action = app_uri();
      $userid    = $kwargs['userid'] ?? null;
      $survey_id = $kwargs['survey_id'] ?? null;

      if(is_null($userid))    { internal_error("missing userid in kwargs");    }
      if(is_null($survey_id)) { internal_error("missing survey_id in kwargs"); }

      echo "<form id='survey' action='$action' method='post'>";
      $nonce = gen_nonce('survey-form');
      $prior_nonce = $_SESSION['prior-nonce'] ?? null;

      add_hidden_input('submit',1);
      add_hidden_input('nonce',$nonce);
      if($prior_nonce) { add_hidden_input('prior-nonce',$prior_nonce); }
      add_hidden_input('ajaxuri',app_uri());
      add_hidden_input('userid',$userid);
      add_hidden_input('survey_id',$survey_id);
    }

    $sections = $content['sections'];
    usort($sections, fn($a,$b) => $a['sequence'] <=> $b['sequence']);
    foreach($sections as $section) {
      $this->add_section($section,$content,$responses,$feedback);
    }

    if($action) { $this->add_submit_bar($state); }

    echo "</form>";
  }

  private function add_submit_bar($state)
  {
    echo "<div class='submit-bar'>";
    // Cases:
    // New:           "Save As Draft", "Submit"
    // Draft:         "Revert to Saved", "Update Draft", "Submit"
    // Draft Updates: "Revert to Saved", "Delete Draft", "Save Draft", "Submit"
    // Submitted:     "Save as Draft", "Submit"
    //
    // submit: Save form data as submitted, clear draft
    // save:   Save form data as draft
    // cancel: Save nothing, reload page
    // delete: Delete draft, reload page
    switch($state) {
    case 'new':
      echo "<div>";
      echo "<button class='submit' type='submit' name='action' value='submit'>Submit</button>";
      echo "<button class='save'   type='submit' name='action' value='save'  >Save As Draft</button>";
      echo "</div>";
      echo "<div></div>"; // empty, but needed for alignment
      break;
    case 'draft':
      echo "<div>";
      echo "<button class='submit' type='submit' name='action' value='submit'>Submit</button>";
      echo "<button class='save'   type='submit' name='action' value='save'  >Update Draft</button>";
      echo "</div><div>";
      echo "<button class='cancel' type='submit' name='action' value='cancel'>Discard Changes</button>";
      echo "</div>";
      break;
    case 'draft_updates':
      echo "<div>";
      echo "<button class='submit' type='submit' name='action' value='submit'>Submit</button>";
      echo "<button class='save'   type='submit' name='action' value='save'  >Update Draft</button>";
      echo "</div><div>";
      echo "<button class='delete' type='submit' name='action' value='delete'>Delete Draft</button>";
      echo "<button class='cancel' type='submit' name='action' value='cancel'>Discard Changes</button>";
      echo "</div>";
      break;
    case 'submitted':
      echo "<div>";
      echo "<button class='submit' type='submit' name='action' value='submit'>Resubmit</button>";
      echo "<button class='save'   type='submit' name='action' value='save'  >Save As Draft</button>";
      echo "</div><div>";
      echo "<button class='cancel' type='submit' name='action' value='cancel'>Cancel</button>";
      echo "</div>";
      break;
    }
    echo "</div>";
  }

  private function add_section($section,$content,$responses,$feedback)
  {
    $sid         = $section['section_id'];
    $name        = $section['name'];
    $collapsible = $section['collapsible'] ?? true;
    $intro       = $section['intro'] ?? '';

    $index = "data-section=$sid";

    if($this->in_box) { echo "</div>"; }

    if($collapsible) {
      echo "<details class='section' $index>";
      echo "<summary><span>$name</span></summary>";
      $closing_tag = "</details>";
    }
    else
    {
      echo "<div class='section' $index>";
      $closing_tag = "</div>";
    }

    if($intro) {
      echo "<div class='section intro' $index>";
      echo MarkdownParser::parse($intro);
      echo "</div>";
    }

    $this->add_questions($sid,$content,$responses);

    $feedback_label = $section['feedback'] ?? false;
    if( $feedback_label )
    {
      echo "<div class='section feedback' $index>";
      echo "<div class='label'>$feedback_label</div>";
      echo "<textarea class='section feedback' name='section-feedback-$sid' placeholder='[optional]'>";
      echo $feedback[$sid] ?? '';
      echo "</textarea>";
      echo "</div>";
    }

    echo $closing_tag;
  }


  private function add_questions($section,$content,$responses)
  {
    # find all the questions that are assigned to this section
    #   (and have an associated sequence value)
    $questions = array_values(array_filter(
      $content['questions'],
      fn($q) => (
        array_key_exists('sequence', $q) && ($q['section'] ?? null) === $section
      )
    ));

    # sort the questions by sequence value
    usort($questions, function($a,$b) {
      return $a['sequence'] <=> $b['sequence'];
    });

    # determine which questions can be put into a grid
    $prev = $questions[0];
    $prev['grid'] = false;
    $prev_can_grid = str_starts_with($prev['type']??'',"SELECT") && ($prev['grouped']==='YES');
    for($i=1; $i<count($questions); ++$i) {
      $cur = $questions[$i];
      $cur_can_grid = str_starts_with($cur['type']??'',"SELECT") && ($cur['grouped']==='YES');
      if( $prev_can_grid && $cur_can_grid ) {
        $questions[$i-1]['grid'] = true;
        $questions[$i]['grid'] = true;
      } else {
        $questions[$i]['grid'] = false;
      }
      $prev_can_grid = $cur_can_grid;
      $prev = $cur;
    }

    # add the questions to the survey form
    $this->follows_info = false;
    foreach($questions as $question) {
      $this->add_question($question,$content,$responses);
    }

    # close the current question box (if open)
    $this->close_box();
  }


  private function add_question($question,$content,$responses)
  {
    $type = strtolower($question['type']);

    $this->start_box($type,$question['grouped']);

    switch($type) {
    case 'info':         
      $this->add_info($question);
      break;
    case 'freetext':
      $this->add_freetext($question,$responses);
      break;
    case 'bool':
      $this->add_bool($question,$responses);
      break;
    case 'select_one':   
      $this->add_select($question,$content['options'],false,$responses); 
      break;                                              
    case 'select_multi':                                  
      $this->add_select($question,$content['options'],true, $responses); 
      break;
    }
  }

  private function start_box($type,$grouped)
  {
    $need_close = false;
    $need_open  = false;

    if($type === 'info') {
      switch($grouped) {
      case "YES":   
        $need_open = true;
        break;
      case "NEW": 
        $need_close = true;
        $need_open  = true; 
        break;
      default:
        $need_close = true;
        break;
      }
    } else {
      $need_open  = true;
      $need_close = $grouped === "NO";
    }

    if($need_close) { $this->close_box(); }
    if($need_open)  { $this->open_box(); }
  }

  private function open_box()
  {
    if( !$this->in_box ) { 
      $this->follows_info = false;

      echo "<div class='question-box'>"; 
      $this->in_box = true;
    }
  }

  private function close_box()
  {
    $this->close_grid();
    if( $this->in_box ) {
      echo "</div>"; 
      $this->in_box = false;
    }
  }

  private function open_grid()
  {
    if( !$this->in_grid ) { 
      $class = 'question select grid';
      if($this->follows_info) { $class = "$class follows-info"; }
      echo "<div class='$class'>"; 
      $this->in_grid = true;
    }
  }

  private function close_grid()
  {
    if( $this->in_grid) { 
      echo "</div>";
      $this->in_grid = false;
    }
  }

  private function add_info($question)
  {
    $id   = $question['id'];
    $info = $question['info'] ?? '';

    $this->close_grid();

    $this->follows_info = true;

    $info = MarkdownParser::parse($info);
    echo "<div class='info question' data-question=$id>$info</div>";
  }

  private function add_freetext($question,$responses)
  {
    $id     = $question['id'];
    $label  = $question['wording'];
    $intro  = $question['intro'] ?? '';
    $popup  = $question['popup'] ?? '';

    $response = $responses[$id]['free_text'] ?? '';

    $input_id = "question-freetext-$id";

    $this->close_grid();

    $class = 'freetext';
    if($this->follows_info) { $class = "$class follows-info"; }
    echo "<div class='question $class' data-question=$id>";

    $indent = ''; // for styling the input box
    if($intro) {
      $intro = MarkdownParser::parse($intro);
      echo "<div class='intro'>$intro</div>";
      $indent = 'indent';
    }
    echo "<div class='input $indent'>";
    echo "<label for='$input_id' class='question'>$label</label>";
    echo "<textarea id='$input_id' type='text' name='$input_id' placeholder='[optional]'>$response</textarea>";
    echo "</div>";
    if($popup) {
      $hint_id  = "hint-$id";
      $hintlock_id  = "hint-lock-$id";
      $popup = MarkdownParser::parse($popup);
      $icon = $this->popup_icon;
      echo "<input id='$hintlock_id' type='checkbox' class='hint-toggle' hidden>";
      echo "<label for='$hintlock_id' class='hint-toggle' data-question-id='$id'>$icon</label>";
      echo "<div id='$hint_id' class='question-hint'>$popup</div>";
    }
    echo "</div>";
  }

  private function add_bool($question,$responses)
  {
    $id        = $question['id'];
    $wording   = $question['wording'];
    $intro     = trim($question['intro'] ?? '');
    $layout    = strtolower($question['layout'] ?? 'left');
    $qualifier = trim($question['qualifier'] ?? '');
    $popup     = $question['popup'] ?? '';

    $selected  = $responses[$id]['selected'] ?? '';
    $qualified = $responses[$id]['qualifier'] ?? '';
    $checked   = $selected ? 'checked' : '';

    $this->close_grid();

    $class = 'bool';
    if($this->follows_info) { $class = "$class follows-info"; }
    echo "<div class='question $class' data-question=$id>";

    if($intro) {
      $intro = MarkdownParser::parse($intro);
      echo "<div class='intro'>$intro</div>";
    }

    $name     = "question-bool-$id";
    $input_id = $name;

    echo "<div class='checkbox $layout'>";
    echo "<input id='$input_id' type='checkbox' name='$name' value='1' $checked>";
    echo "<label for='$input_id' class='question'>$wording</label>";
    echo "</div>";

    if($qualifier) {
      $qualifier_id = "question-qualifier-$id";
      echo "<div class='qualifier'>";
      echo "<label for='$qualifier_id' class='qualifier'>$qualifier</label>";
      echo "<textarea id='$qualifier_id' class='qualifier' type='text' name='$qualifier_id' placeholder='[optional]' rows='1'>$qualified</textarea>";
      echo "</div>";
    }

    if($popup) {
      $hint_id = "hint-$id";
      $hintlock_id = "hint-lock-$id";
      $popup = MarkdownParser::parse($popup);
      $icon = $this->popup_icon;
      echo "<input id='$hintlock_id' type='checkbox' class='hint-toggle' hidden>";
      echo "<label for='$hintlock_id' class='hint-toggle' data-question-id='$id'>$icon</label>";
      echo "<div id='$hint_id' class='question-hint'>$popup</div>";
    }

    echo "</div>";
  }

  private function add_select($question,$option_strings,$multi,$responses)
  {
    $id        = $question['id'];
    $wording   = $question['wording'];
    $intro     = trim($question['intro'] ?? '');
    $layout    = strtolower($question['layout'] ?? 'row');
    $qualifier = trim($question['qualifier'] ?? '');
    $popup     = $question['popup'] ?? '';
    $options   = $question['options'] ?? [];

    $other_flag = $question['other_flag'] ?? false;
    $other      = $question['other'] ?? 'Other';
    
    $in_grid = $question['grid']??false;

    if(!$options) {
      log_warning("Failed to render select question $id ($wording) as there were no options provided");
      return;
    }

    if($multi) {
      $type = 'checkbox';
      $class = 'select multi';
      $name = "question-multi-$id";
    } else {
      $type = 'radio';
      $class = 'select one';
      $name = "question-select-$id";
    }

    $response = $responses[$id] ?? null;
    $selected    = $response['selected'] ?? [];
    $other_value = $response['other'] ?? '';
    $qualified   = $response['qualifier'] ?? '';

    if($in_grid) {
      $this->open_grid();
    }
    else {
      $this->close_grid();
      if($this->follows_info) { $class = "$class follows-info"; }
      echo "<div class='question $class' data-question=$id>";
    }

    if($intro) {
      $intro = MarkdownParser::parse($intro);
      echo "<div class='intro'>$intro</div>";
    }

    if(!$in_grid) { echo "<div class='options-box'>"; }

    echo "<div class='options wording'>$wording</div>";
    echo "<div class='options wrapper $layout'>";
    foreach($options as $option) {
      $input_id = "$name-$option";
      $input_name = $multi ? $input_id : $name;
      $option_str = $option_strings[$option];
      $checked = in_array($option,$selected) ? 'checked' : '';
      echo "<div class='option'>";
      echo "<input id='$input_id' type='$type' name='$input_name' value='$option' $checked>";
      echo "<label for='$input_id'>$option_str</label>";
      echo "</div>";
    }
    if($other_flag) {
      $input_id = "$name-has-other";
      $input_name = $multi ? $input_id : $name;
      $value      = $multi ? 1 : 0;
      $other_id = "$name-other";
      $checked = in_array(0,$selected) ? 'checked' : '';
      echo "<div class='option'>";
      echo "<input id='$input_id' type='$type' class='has-other' name='$input_name' value='$value' $checked>";
      echo "<textarea id='$other_id' class='other' name='$other_id' rows='1' placeholder='$other'>$other_value</textarea>";
      echo "</div>";
    }
    if($popup) {
      $hint_id = "hint-$id";
      $hintlock_id = "hint-lock-$id";
      $icon = $this->popup_icon;
      echo "<label for='$hintlock_id' class='hint-toggle' data-question-id='$id'>$icon</label>";
    }
    echo "</div>"; // option-wrapper

    if(!$in_grid) { echo "</div>"; } // options

    if($qualifier) {
      $qualifier_id  = "question-qualifier-$id";
      echo "<div class='qualifier'>";
      echo "<label for='$qualifier_id' class='qualifier'>$qualifier:</label>";
      echo "<textarea id='$qualifier_id' class='qualifier' type='text' name='$qualifier_id' placeholder='[optional]' rows='1'>$qualified</textarea>";
      echo "</div>";
    }

    if($popup) {
      $popup = MarkdownParser::parse($popup);
      echo "<input id='$hintlock_id' type='checkbox' class='hint-toggle' hidden>";
      echo "<div id='$hint_id' class='question-hint'>$popup</div>";
    }

    if(!$in_grid) { echo "</div>"; }
  }
};

function render_survey($state, $content, $kwargs=[])
{
  $re = new RenderEngine();
  $re->render($state, $content, $kwargs);
}

