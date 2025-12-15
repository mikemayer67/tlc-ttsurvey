<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Print Render --------------");

handle_warnings();

require_once(app_file('include/logger.php'));
require_once(app_file('survey/markdown.php'));

function render_header($title)
{
  $logo_file = app_logo();
  $logo_uri  = $logo_file ? img_uri($logo_file) : '';

  echo "<table class='ttt-header'><tr>";
  if($logo_uri) { echo "<td class='logo'><img class='ttt-logo' src='$logo_uri'></td>"; }
  echo "<td class='title'><span class='ttt-title'>$title</span></td>";
  echo "</tr></table>";
}

function render_footer()
{
  echo "<div class='ttt-footer page-{PAGENO}'>";
  echo "page {PAGENO} of {nbpg}";
  echo "</div>";
}

function tdiv($html,$class='') {
  echo "<div class='$class'><table><tr><td>$html</td></tr></table></div>";
}

function render_printable($mpdf,$content)
{
  $re = new PrintRenderEngine($mpdf);
  $re->render($content);
}

class PrintRenderEngine 
{
  private $mpdf          = null;
  private $first_section = true;
  private $in_box        = false;
  private $in_grid       = false;
  private $follows_info  = false;

  public function __construct($mpdf) 
  {
    $this->mpdf = $mpdf;
  }

  public function render($content)
  {
    $this->in_box       = false;
    $this->in_grid      = false;
    $this->follows_info = false;

    $sections = $content['sections'];
    usort($sections, fn($a,$b) => $a['sequence'] <=> $b['sequence']);
    foreach($sections as $section) {
      $this->add_section($section,$content);
    }
  }

  private function add_section($section,$content)
  {
    $sid         = $section['section_id'];
    $name        = $section['name'];
    $collapsible = $section['collapsible'] ?? true;
    $intro       = $section['intro'] ?? '';

    // close the prior <div> box if it is still open
    if($this->in_box) { echo "</div>"; }

    if($this->first_section) {
      $this->first_section = false;
    } else {
      echo "<pagebreak/>";
    }

    if($collapsible) {
      tdiv($name,'section label');
    }

    if($intro) {
      $intro = MarkdownParser::parse($intro);
      tdiv($intro,'section intro');
    }
    
//    $this->add_questions($sid,$content);

    $feedback_label = $section['feedback'] ?? false;
    if( $feedback_label )
    {
      echo "<div class='section feedback'>";
      echo "<div class='label'>$feedback_label</div>";
      echo "<div class='textarea'></div>";
      echo "</div>";
    }
  }

  /*
  private function add_questions($sid,$content)
  {
    # find all the questions that are assigned to this section
    #   (and have an associated sequence value)
    $questions = array_values(array_filter(
      $content['questions'],
      fn($q) => (
        array_key_exists('sequence', $q) &&
        ($q['section'] ?? null) === $sid
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





   */


}
