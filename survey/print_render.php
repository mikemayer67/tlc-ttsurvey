<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

// NOTE: THE FOLLOWING IS HIGHLY REDUNDANT WITH surveys/render.php
//   But at this point, I am not attempting a refactor of render.php to 
//   allow for generating the printable page

log_dev("-------------- Start of Print Render --------------");

handle_warnings();

require_once(app_file('include/logger.php'));
require_once(app_file('survey/markdown.php'));

class PrintRenderEngine 
{
  private $in_box       = false;
  private $in_grid      = false;
  private $follows_info = false;

  public function render($content)
  {
    $this->in_box       = false;
    $this->in_grid      = false;
    $this->follows_info = false;

    foreach($content['sections'] as $section) {
      $this->add_section($section,$content);
    }
  }

  private function add_section($section,$content)
  {
    $sequence    = $section['sequence'];
    $name        = $section['name'];
    $collapsible = $section['collapsible'] ?? true;
    $intro       = $section['intro'] ?? '';

    if($this->in_box) { echo "</div>"; }

    if($collapsible) {
      echo "<div class='section label'>$name</div>";
    }

    if($intro) {
      echo "<div class='section intro'>";
      echo MarkdownParser::parse($intro);
      echo "</div>";
    }

    $this->add_questions($sequence,$content);

    $feedback_label = $section['feedback'] ?? false;
    if( $feedback_label )
    {
      echo "<div class='section feedback'>";
      echo "<div class='label'>$feedback_label</div>";
      echo "<textarea class='section feedback'></textarea>";
      echo "</div>";
    }
  }


  private function add_questions($section,$content)
  {
    # find all the questions that are assigned to this section
    #   (and have an associated sequence value)
    $questions = array_values(array_filter(
      $content['questions'],
      fn($q) => (
        array_key_exists('sequence', $q) &&
        ($q['section'] ?? null) === $section
      )
    ));

    # sort the questions by sequence value
    usort($questions, function($a,$b) {
      return $a['sequence'] <=> $b['sequence'];
    });

    # determine which questions can be put into a grid
    $prev = $questions[0];
    $prev['grid'] = false;
    $prev_can_grid = str_starts_with($prev['type'],"SELECT") && ($prev['grouped']==='YES');
    for($i=1; $i<count($questions); ++$i) {
      $cur = $questions[$i];
      $cur_can_grid = str_starts_with($cur['type'],"SELECT") && ($cur['grouped']==='YES');
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
      $this->add_question($question,$content);
    }

    # close the current question box (if open)
    $this->close_box();
  }


  private function add_question($question,$content)
  {
    $type = strtolower($question['type']);

    $this->start_box($type,$question['grouped']);

    switch($type) {
    case 'info':         
      $this->add_info($question);
      break;
    case 'freetext':
      $this->add_freetext($question);
      break;
    case 'bool':
      $this->add_bool($question);
      break;
    case 'select_one':   
      $this->add_select($question,$content['options'],false); 
      break;                                              
    case 'select_multi':                                  
      $this->add_select($question,$content['options'],true); 
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
    $info = $question['info'] ?? '';

    $this->close_grid();
    $this->follows_info = true;

    $info = MarkdownParser::parse($info);
    echo "<div class='info'>$info</div>";
  }

  private function add_freetext($question)
  {
    $label  = $question['wording'];
    $intro  = $question['intro'] ?? '';

    $this->close_grid();

    $follows = $this->follows_info ? 'follows-info' : '';
    echo "<div class='freetext $follows'>";

    $indent = ''; // for styling the input box
    if($intro) {
      $intro = MarkdownParser::parse($intro);
      echo "<div class='intro'>$intro</div>";
      $indent = 'indent';
    }
    echo "<div class='input $indent'>";
    echo "<div class='label'>$label</div>";
    echo "<div class='textarea'></div>";
    echo "</div>";

    echo "</div>";
  }

  private function add_bool($question)
  {
    $wording   = $question['wording'];
    $intro     = $question['intro'] ?? '';
    $layout    = strtolower($question['layout'] ?? 'left');
    $qualifier = $question['qualifier'] ?? '';

    $this->close_grid();

    $follows = $this->follows_info ? 'follows-info' : '';
    echo "<div class='bool $follows'>";

    $indent = '';
    if($intro) {
      $intro = MarkdownParser::parse($intro);
      echo "<div class='intro'>$intro</div>";
      $indent = 'indent';
    }

    echo "<div class='wrapper $layout $indent'>";
    echo "<div class='checkbox'></div>";
    echo "<div class='wording'>$wording</div>";
    echo "</div>";

    if($qualifier) {
      echo "<div class='qualifier'>";
      echo "<div class='label'>$qualifier</div>";
      echo "<div class='textarea'></div>";
      echo "</div>";
    }

    echo "</div>";
  }

  private function add_select($question,$option_strings,$multi)
  {
    $wording   = $question['wording'];
    $intro     = $question['intro'] ?? '';
    $layout    = strtolower($question['layout'] ?? 'row');
    $qualifier = $question['qualifier'] ?? '';
    $options   = $question['options'] ?? [];
    $other     = ($question['other_flag'] ?? false) ? 'other' : '';
    
    $in_grid = $question['grid']??false;

    if(!$options) {
      log_warning("Failed to render select question $id ($wording) as there were no options provided");
      return;
    }

    if($multi) {
      $type = 'checkbox';
      $class = 'select multi';
    } else {
      $type = 'radio';
      $class = 'select one';
    }

    if($in_grid) {
      $this->open_grid();
    }
    else {
      $this->close_grid();
      $follows = $this->follows_info ? 'follows-info' : '';
      echo "<div class='$class $follows' data-question=$id>";
    }

    $indent = '';
    if($intro) {
      echo "<div class='intro'>$intro</div>";
      $indent = 'indent';
    }

    if(!$in_grid) { echo "<div class='options-box'>"; }

    echo "<div class='wording $indent $other'>$wording</div>";
    echo "<div class='wrapper $layout'>";
    foreach($options as $option) {
      $option_str = $option_strings[$option];
      echo "<div class='option'>";
      echo "<div class='option-str'>$option_str</div>";
      echo "<div class='$type'></div>";
      echo "</div>";
    }
    if($other) {
      $other_label = $question['other'] ?? 'Other';
      echo "<div class='option'>";
      echo "<div class='textarea other'></div>";
      echo "<div class='other label'>$other_label</div>";
      echo "<div class='$type'></div>";
      echo "</div>";
    }
    echo "</div>"; // option-wrapper

    if(!$in_grid) { echo "</div>"; } // options

    if($qualifier) {
      echo "<div class='qualifier'>";
      echo "<div class='label'>$qualifier</div>";
      echo "<div class='textarea'></div>";
      echo "</div>";
    }

    if(!$in_grid) { echo "</div>"; }
  }
};

function render_printable($content)
{
  $re = new PrintRenderEngine();
  $re->render($content);
}

