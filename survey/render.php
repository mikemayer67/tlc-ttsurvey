<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Render --------------");

handle_warnings();

require_once(app_file('vendor/autoload.php'));
use League\CommonMark\CommonMarkConverter;
use HTMLPurifier;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_ErrorCollector;

require_once(app_file('include/logger.php'));
require_once(app_file('survey/markdown.php'));

class RenderEngine 
{
  private $popup_icon = null;
  private $sections   = null;
  private $questions  = null;
  private $option_map = null;

  private $is_preview = false;
  private $preview_js = true;

  private $box_started = false;

  function __construct($content, $kwargs=[])
  {
    $this->is_preview = $kwargs['is_preview'] ?? false; 
    $this->preview_js = $kwargs['preview_js'] ?? true;

    $this->popup_icon = "<img class='popup' src='" . img_uri('icons8/info.png') . "'></img>";

    $this->sections   = $content['sections'];
    $this->questions  = $content['questions'];
    $this->option_map = $content['options']; 
  }

  public function render($userid=null)
  {
    todo("add survey form action");

    $this->box_started = false;

    echo "<form id='survey'>";
    foreach($this->sections as $section) {
      $this->add_section($section);
    }
    $this->add_submit_bar();
    echo "</form>";

    if($preview_js || !$is_preview) {
      echo "<script type='module' src='", js_uri('survey'), "'></script>";
    }
  }

  private function add_submit_bar()
  {
    echo "<div class='submit-bar'>";
    if($this->is_preview) {
      // don't want to actually submit this if it's only a preview
      echo "<input id='submit' class='submit' type='button' value='Submit'>";
      echo "<input id='revert' class='revert hidden' type='button' value='Start Over'>";
    } else {
      echo "<input id='submit' class='submit' type='submit' value='Submit'>";
      echo "<input id='revert' class='revert hidden' type='submit' value='Start Over' formnovalidate>";
    }
    echo "</div>";
  }

  private function add_section($section)
  {
    $sequence    = $section['sequence'];
    $name        = $section['name'];
    $collapsible = $section['collapsible'] ?? true;
    $intro       = $section['intro'] ?? '';
    $feedback    = $section['feedback'] ?? false;

    $index = "data-section=$sequence";

    if($this->box_started) { echo "</div>"; }

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

    $this->add_questions($sequence);

    if($feedback) {
      echo "<div class='section feedback' $index>";
      echo "<div class='label'>$feedback</div>";
      echo "<textarea class='section feedback' name='section-feedback-$sequence' placeholder='[optional]'>";
      echo "</textarea>";
      echo "</div>";
    }

    echo $closing_tag;
  }


  private function add_questions($section)
  {
    # find all the questions that are assined to this section
    $questions = [];
    foreach($this->questions as $question) {
      # if question doesn't have an assigned sequence, ignore it
      if(array_key_exists('sequence',$question)) {
        # if section doesn't match the current section, ignore it
        if( ($question['section']??null) === $section ) {
          $questions[] = $question;
        }
      }
    }

    # sort the questions by sequence value
    usort($questions, function($a,$b) {
      return $a['sequence'] <=> $b['sequence'];
    });

    # add the questions to the survey form
    foreach($questions as $question) {
      $this->add_question($question);
    }

    # close the current question box (if open)
    $this->close_box();
  }


  private function add_question($question)
  {
    $type = strtolower($question['type']);

    $this->start_box($type,$question['grouped']);

    switch($type) {
    case 'info':         $this->add_info($question);         break;
    case 'freetext':     $this->add_freetext($question);     break;
    case 'bool':         $this->add_bool($question);         break;
    case 'select_one':   $this->add_select($question,false); break;
    case 'select_multi': $this->add_select($question,true);  break;

    default:
      echo "<h2>$type</h2>";
      echo "<pre>".print_r($question,true),"</pre>";
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
      case "BOXED": 
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
    if( !$this->box_started ) { 
      echo "<div class='question-box'>"; 
      $this->box_started = true;
    }
  }

  private function close_box()
  {
    if( $this->box_started ) {
      echo "</div>"; 
      $this->box_started = false;
    }
  }

  private function add_info($question)
  {
    $id   = $question['id'];
    $info = $question['info'] ?? '';

    $info = MarkdownParser::parse($info);
    echo "<div class='info question' data-question=$id>$info</div>";
  }

  private function add_freetext($question)
  {
    $id     = $question['id'];
    $label  = $question['wording'];
    $intro  = $question['intro'] ?? '';
    $popup  = $question['popup'] ?? '';
    $indent = ''; // for styling the input box

    $input_id = "question-input-$id";
    $hint_id  = "hint-toggle-$id";

    echo "<div class='freetext question' data-question=$id>";
    $indent = ''; // for styling the input box
    if($intro) {
      $intro = MarkdownParser::parse($intro);
      echo "<div class='intro'>$intro</div>";
      $indent = 'indent';
    }
    echo "<div class='input $indent'>";
    echo "<label for='$input_id' class='question'>$label</label>";
    echo "<textarea id='$input_id' type='text' name='$input_id' placeholder='[optional]'></textarea>";
    echo "</div>";
    if($popup) {
      $popup = MarkdownParser::parse($popup);
      $icon = $this->popup_icon;
      echo "<input id='$hint_id' type='checkbox' class='hint-toggle' hidden>";
      echo "<label for='$hint_id' class='hint-toggle'>$icon</label>";
      echo "<div class='question-hint'>$popup</div>";
    }
    echo "</div>";
  }

  private function add_bool($question)
  {
    $id        = $question['id'];
    $wording   = $question['wording'];
    $intro     = $question['intro'] ?? '';
    $layout    = strtolower($question['layout'] ?? 'left');
    $qualifier = $question['qualifier'] ?? '';
    $popup     = $question['popup'] ?? '';

    echo "<div class='bool question' data-question=$id>";

    if($intro) {
      $intro = MarkdownParser::parse($intro);
      echo "<div class='intro'>$intro</div>";
    }

    $name     = "question-input-$id";
    $input_id = $name;

    echo "<div class='checkbox $layout'>";
    echo "<input id='$input_id' type='checkbox' name='$name'>";
    echo "<label for='$input_id' class='question'>$wording</label>";
    echo "</div>";

    if($qualifier) {
      $qualifier_id = "question-qualifier-$id";
      echo "<div class='qualifier'>";
      echo "<label for='$qualifier_id' class='qualifier'>$qualifier</label>";
      echo "<textarea id='$qualifier_id' class='qualifier' type='text' name='$qualifier_id' placeholder='[optional]' rows='1'></textarea>";
      echo "</div>";
    }

    if($popup) {
      $hint_id = "hint-toggle-$id";
      $popup = MarkdownParser::parse($popup);
      $icon = $this->popup_icon;
      echo "<input id='$hint_id' type='checkbox' class='hint-toggle' hidden>";
      echo "<label for='$hint_id' class='hint-toggle'>$icon</label>";
      echo "<div class='question-hint'>$popup</div>";
    }

    echo "</div>";
  }

  private function add_select($question,$multi)
  {
    $id        = $question['id'];
    $wording   = $question['wording'];
    $intro     = $question['intro'] ?? '';
    $layout    = strtolower($question['layout'] ?? 'row');
    $qualifier = $question['qualifier'] ?? '';
    $popup     = $question['popup'] ?? '';
    $options   = $question['options'] ?? [];

    $other_flag = $question['other_flag'] ?? false;
    $other      = $question['other'] ?? 'Other';

    if(!$options) {
      log_warning("Failed to render select question $id ($wording) as there were no options provided");
      return;
    }

    if($multi) {
      $input_id = "$input_id\[\]";
      $type = 'checkbox';
      $class = 'select multi';
    } else {
      $type = 'radio';
      $class = 'select one';
    }

    echo "<div class='question $class' data-question=$id>";

    if($intro) {
      echo "<div class='intro'>$intro</div>";
    }

    $name = "question-input-$id";

    echo "<div class='options'>";
    echo "<div class='wording'>$wording</div>";
    echo "<div class='wrapper $layout'>";
    foreach($options as $option) {
      $input_id = "$name-$option";
      $option_str = $this->option_map[$option] ?? "option #$option";
      echo "<div class='option'>";
      echo "<input id='$input_id' type='$type' name='$name' value='$option'>";
      echo "<label for='$input_id'>$option_str</label>";
      echo "</div>";
    }
    if($other_flag) {
      $input_id = "$name-has-other";
      $other_id = "$name-other";
      echo "<div class='option'>";
      echo "<input id='$input_id' type='$type' class='has-other' name='$name' value='0'>";
      echo "<textarea id='$other_id' class='other' name='$other_id' rows='1' placeholder='$other'></textarea>";
      echo "</div>";
    }
    echo "</div>"; // option-wrapper
    echo "</div>"; // options

    if($qualifier) {
      $qualifier_id  = "question-qualifier-$id";
      echo "<div class='qualifier'>";
      echo "<label for='$qualifier_id' class='qualifier'>$qualifier:</label>";
      echo "<textarea id='$qualifier_id' class='qualifier' type='text' name='$qualifier_id' placeholder='[optional]' rows='1'></textarea>";
      echo "</div>";
    }

    if($popup) {
      $hint_id       = "hint-toggle-$id";
      $popup = MarkdownParser::parse($popup);
      $icon = $this->popup_icon;
      echo "<input id='$hint_id' type='checkbox' class='hint-toggle' hidden>";
      echo "<label for='$hint_id' class='hint-toggle'>$icon</label>";
      echo "<div class='question-hint'>$popup</div>";
    }

    echo "</div>";
  }
};

function render_survey($userid, $content, $kwargs=[])
{
  $re = new RenderEngine($content,$kwargs);
  $re->render($userid);
}

