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

  function __construct($content, $kwargs=[])
  {
    $this->popup_icon = "<img class='popup' src='" . img_uri('icons8/info.png') . "'></img>";

    $this->sections  = $content['sections'];
    $this->questions = $content['questions'];
    $this->options   = $content['options']; 
  }

  public function render($userid=null)
  {
    echo "<form id='survey'>";
    foreach($this->sections as $section) {
      $this->add_section($section);
    }
    echo "</form>";
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
      echo "<summary>$name</summary>";
      $closing_tag = "</details>";
    }
    else
    {
      echo "<div class='section' $index>";
      $closing_tag = "</div>";
    }

    if($description) {
      echo "<div class='description' $index>";
      echo MarkdownParser::parse($description);
      echo "</div>";
    }

    $this->add_questions($sequence);

    if($feedback) {
      echo "<div class='feedback' $index>";
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
    case 'select_one':   $this->add_select_one($question);   break;
    case 'select_multi': $this->add_select_multi($question); break;

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
    $description = $question['description'] ?? '';
    $qualifier   = $question['qualifier'] ?? '';
    $popup       = $question['popup'] ?? '';

    $input_id     = "question-input-$id";
    $qualifer_id  = "question-qualifier-$id";
    $hint_id      = "hint-toggle-$id";

    echo "<div class='bool question' data-question=$id>";
    echo "<input id='$input_id' type='checkbox' name='$input_id'>";
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
    if($qualifier) {
      echo "<label for='$qualifier_id' class='qualifier'>$qualifier:</label>";
      echo "<textarea id='$qualifier_id' class='qualifier' type='text' name='$qualifier_id'></textarea>";
    }
    echo "</div>";
  }

  private function add_select_one($question)
  {
    $id          = $question['id'];
    $type        = $multi ? 'multi' : 'one';
    $wording     = $question['wording'];
    $description = $question['description'] ?? '';
    $other       = $question['other'] ?? '';
    $qualifier   = $question['qualifier'] ?? '';
    $popup       = $question['popup'] ?? '';
    $options     = $question['options'] ?? [];

    $options = array_map(fn($opt) => $opt[0], $options);

    if(!$options) {
      log_warning("Failed to render select question $id ($wording) as there were no options provided");
      return;
    }

    $input_id     = "question-input-$id";
    $qualifer_id  = "question-qualifier-$id";
    $hint_id      = "hint-toggle-$id";

    echo "<div class='select one question' data-question=$id>";
    echo "<fieldset class='select one'>";
//    echo "<legend class='select one'>$wording</legend>";
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
      echo "<input id='$input_id' type='radio' name='$input_id' value='$option'>";
      echo $option;
      echo "</label>";
    }
    echo "</fieldset>";
    if($qualifier) {
      echo "<label for='$qualifier_id' class='qualifier'>$qualifier:</label>";
      echo "<textarea id='$qualifier_id' class='qualifier' type='text' name='$qualifier_id'></textarea>";
    }

    echo "</div>";
  }

  private function add_select_multi($question)
  {
    echo "<pre>".print_r($question,true)."</pre>";
  }
};

function render_survey($userid, $content, $kwargs=[])
{
  $re = new RenderEngine($content,$kwargs);
  $re->render($userid);
}

