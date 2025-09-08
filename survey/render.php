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
  private $sections  = null;
  private $questions = null;
  private $options   = null;

  private $is_preview = false;
  private $preview_js = true;

  function __construct($content, $kwargs=[])
  {
    $this->is_preview = $kwargs['is_preview'] ?? false; 
    $this->preview_js = $kwargs['preview_js'] ?? true;

    $this->popup_icon = "<img class='popup' src='" . img_uri('icons8/info.png') . "'></img>";

    $this->sections  = $content['sections'];
    $this->questions = $content['questions'];
    $this->options   = $content['options']; 
  }

  public function render($userid=null)
  {
    todo("add survey form action");

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
    $description = $section['description'] ?? '';
    $feedback    = $section['feedback'] ?? false;

    $index = "data-section=$sequence";

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

    if($description) {
      echo "<div class='section description' $index>";
      echo MarkdownParser::parse($description);
      echo "</div>";
    }

    $this->add_questions($sequence);

    if($feedback) {
      echo "<div class='section feedback' $index>";
      echo "<div class='label'>$feedback</div>";
      echo "<textarea class='section feedback' name='section-feedback-$sequence'>";
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
  }


  private function add_question($question)
  {
    $type = strtolower($question['type']);

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

  private function add_info($question)
  {
    $id   = $question['id'];
    $info = $question['info'] ?? '';

    echo "<div class='info question' data-question=$id>";
    echo MarkdownParser::parse($info);
    echo "</div>";
  }

  private function add_freetext($question)
  {
    $id          = $question['id'];
    $label       = $question['wording'];
    $description = $question['description'] ?? '';
    $popup       = $question['popup'] ?? '';

    $input_id = "question-input-$id";
    $hint_id  = "hint-toggle-$id";

    echo "<div class='freetext question' data-question=$id>";
    echo "<label for='$input_id' class='question'>$label</label>";
    if($popup) {
      $icon = $this->popup_icon;
      echo "<input id='$hint_id' type='checkbox' class='hint-toggle' hidden>";
      echo "<label for='$hint_id' class='hint-toggle'>$icon</label>";
      echo "<div class='question-hint'>$popup</div>";
    }
    if($description) {
      echo "<div class='description'>$description</div>";
    }
    echo "<textarea id='$input_id' type='text' name='$input_id'></textarea>";
    echo "</div>";
  }

  private function add_bool($question)
  {
    $id          = $question['id'];
    $wording     = $question['wording'];
    $layout      = strtolower($question['layout'] ?? 'left');
    $description = $question['description'] ?? '';
    $qualifier   = $question['qualifier'] ?? '';
    $popup       = $question['popup'] ?? '';

    $input_id     = "question-input-$id";
    $qualifier_id = "question-qualifier-$id";
    $hint_id      = "hint-toggle-$id";

    echo "<div class='bool question' data-question=$id>";
    if($description) {
      $description = MarkdownParser::parse($description);
      echo "<div class='description'>$description</div>";
    }
    echo "<div class='checkbox $layout'>";
    echo "<input id='$input_id' type='checkbox' name='$input_id'>";
    echo "<label for='$input_id' class='question'>$wording</label>";
    echo "</div>";
    if($qualifier) {
      echo "<div class='qualifier'>";
      echo "<label for='$qualifier_id' class='qualifier'>$qualifier</label>";
      echo "<textarea id='$qualifier_id' class='qualifier' type='text' name='$qualifier_id' placeholder='optional' rows='1'></textarea>";
      echo "</div>";
    }
    if($popup) {
      $icon = $this->popup_icon;
      echo "<input id='$hint_id' type='checkbox' class='hint-toggle' hidden>";
      echo "<label for='$hint_id' class='hint-toggle'>$icon</label>";
      echo "<div class='question-hint'>$popup</div>";
    }
    echo "</div>";
  }

  private function add_select($question,$multi)
  {
    $id          = $question['id'];
    $wording     = $question['wording'];
    $description = $question['description'] ?? '';
    $qualifier   = $question['qualifier'] ?? '';
    $popup       = $question['popup'] ?? '';
    $options     = $question['options'] ?? [];

    $other_flag = $question['other_flag'] ?? false;
    $other_str  = $question['other_str'] ?? 'Other';

    if(!$options) {
      log_warning("Failed to render select question $id ($wording) as there were no options provided");
      return;
    }

    $input_id     = "question-input-$id";
    $qualifier_id  = "question-qualifier-$id";
    $hint_id      = "hint-toggle-$id";

    if($multi) {
      $input_id = "$input_id\[\]";
      $type = 'checkbox';
      $class = 'select multi';
    } else {
      $type = 'radio';
      $class = 'select one';
    }

    echo "<div class='question $class' data-question=$id>";
    echo "<fieldset class='$class'>";
    echo "<label for='$input_id' class='question'>$wording</label>";
    if($popup) {
      $icon = $this->popup_icon;
      echo "<input id='$hint_id' type='checkbox' class='hint-toggle' hidden>";
      echo "<label for='$hint_id' class='hint-toggle'>$icon</label>";
      echo "<div class='question-hint'>$popup</div>";
    }
    if($description) {
      echo "<div class='description'>$description</div>";
    }
    foreach($options as $option) {
      echo "<label>";
      echo "<input id='$input_id' type='$type' name='$input_id' value='$option'>";
      echo $option;
      echo "</label>";
    }
    if($other_flag) {
      $other_id = "question-input-other-$id";
      echo "<label>";
      echo "<input id='$input_id' type='$type' name='$input_id' value='0'>";
      echo "<span class='other label'>$other_str:</span>";
      echo "<input id='$other_id' type='text' name='$other_id'></input>";
      echo "</label>";
    }
    echo "</fieldset>";
    if($qualifier) {
      echo "<label for='$qualifier_id' class='qualifier'>$qualifier:</label>";
      echo "<textarea id='$qualifier_id' class='qualifier' type='text' name='$qualifier_id'></textarea>";
    }

    echo "</div>";
  }
};

function render_survey($userid, $content, $kwargs=[])
{
  $re = new RenderEngine($content,$kwargs);
  $re->render($userid);
}

