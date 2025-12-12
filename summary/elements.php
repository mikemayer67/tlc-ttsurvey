<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));

function start_summary_page($kwargs)
{
  start_header($kwargs['title']);
  add_tab_name('ttt_summary');
  add_js_resources('summary');
  add_css_resources('summary');
  add_notebook_css($kwargs['tab_ids']);
  end_header();

  add_navbar('summary',$kwargs);

  start_body();
}

function add_navbar_center_summary($kwargs)
{
  echo '<b>Summary of Responses</b>';
}

function add_navbar_right_summary($kwargs)
{
  add_return_to_survey();
}

function add_notebook_css($tab_ids)
{
  echo "<style>\n";
  foreach($tab_ids as $id) {
    echo "#tab-cb-$id:checked ~ #panel-$id { display:block; }\n";
  }

  echo implode(
    ",\n", 
    array_map( function($id) { return "#tab-cb-$id:checked ~ div.tabs label.tab-$id"; }, $tab_ids )
  );
  echo "{";
  echo " background:#f4f4f4;";
  echo " border-bottom: solid #f4f4f4 2px;";
  echo "}";
  
  echo "</style>";
}

class SectionPanel
{
  private $sid       = null;
  private $section   = null;
  private $questions = null;
  private $options   = null;
  private $responses = null;
  private $feedback  = null;

  private $indent    = false;
  private $grouped   = false;

  function __construct($sid,$content,$responses)
  {
    $this->sid       = $sid;
    $this->section   = $content['sections'][$sid];
    $this->options   = $content['options'];
    $this->responses = $responses;

    $questions = $content['questions'];
    $questions = array_filter($questions, fn($a) => ($a['section']??null) === $sid );

    uasort($questions,fn($a,$b) => $a['sequence'] <=> $b['sequence']);

    $this->questions = $questions;
  }

  public function add()
  {
    $sid  = $this->sid;
    echo "<div id='panel-$sid' class='panel panel-$sid'>";

    $this->indent = false;
    foreach($this->questions as $question) {
      $type    = strtolower($question['type']);

      if($question['grouped'] === 'NO') {
        $this->grouped = false;
        $this->indent = false;
      } else {
        $this->grouped = true;
        // don't start indentation unless info text is found
      }

      $this->add_question($question);
    }

    // Add Section Feedback, if applicable
    $this->add_feedback();

    echo "</div>";
  }

  private function add_question($question)
  {
    $type = strtolower($question['type']??'');

    if( $type === 'info' ) {
      $this->add_info_text($question);
      return;
    }

    $qid     = $question['id'];
    $wording = $question['wording'];
    $indent  = $this->indent ? 'indent' : '';
    $class   = str_replace('_',' ',$type);

    echo "<div class='$class question $indent'>";
    echo "<div class='label'>$wording</div>";

    $responses = $this->responses['questions'][$qid] ?? [];
    switch($type) {
      case 'bool':         $this->add_bool_responses($question,$responses);         break;
      case 'freetext':     $this->add_freetext_responses($question,$responses);     break;
      case 'select_one':   $this->add_select_one_responses($question,$responses);   break;
      case 'select_multi': $this->add_select_multi_responses($question,$responses); break;
    }
    echo "</div>";
  }

  private function add_info_text($question)
  {
    // only display info text inside of question boxes
    //   questions prior to any info text should stand alone
    //   questions after the info text should be indented

    if(!$this->grouped) { return; }
    $this->indent = true;

    $info_text = $question['info'];
    echo "<div class='info text question'>";
    echo "<div class='label'>$info_text</div>";
    echo "</div>";
  }

  private function add_feedback()
  {
    $feedback = $this->section['feedback'] ?? null;
    if(!$feedback) { return; }

    echo "<div class='feedback responses'>";
    echo "<div class='label'>$feedback</div>";
    $responses = $this->responses['sections'][$this->sid] ?? [];
    if($responses) {
      echo "<table class='section-feedback'>";
      foreach($responses as $userid=>$response) {
        $user = User::from_userid($userid);
        $name = $user->fullname();
        echo "<tr><td class='name'>$name:</td><td class='response'>$response</td></tr>";
      }
      echo "</table>";
    } else {
      echo "<div class='no-feedback'>No responses</div>";
    }
    echo "</div>";
  }

  private function add_bool_responses($question,$responses)
  {
    if(!$responses) {
      echo "<div class='none'>(nobody)</div>";
      return;
    }

    $qualifiers = [];
    echo "<div class='bool responses resizable-list'>";
    foreach($responses as $response) {
      $userid = $response['userid'];
      $user   = User::from_userid($userid);
      $name   = $user->fullname();
      $qual   = $response['qualifier']??null;

      $mark = '';
      if($qual) {
        $mark = 'qual';
        $qualifiers[$name] = $qual;
      }

      echo "<div class='name $mark'>$name</div>";
    }
    echo "</div>";

    if($qualifiers) {
      echo "<div class='qualifiers'>";
      echo "<table class='qualifiers'>";
      foreach($qualifiers as $name=>$qual) {
        echo "<tr><td class='name'>$name:</td><td class='qual'>$qual</td></tr>";
      }
      echo "</table>";
      echo "</div>";
    }
  }

  private function add_freetext_responses($question,$responses)
  {
    if(!$responses) {
      echo "<div class='none'>(nobody)</div>";
      return;
    }

    echo "<div class='freetext responses'>";
    echo "<table>";
    foreach($responses as $response) {
      $userid = $response['userid'];
      $user   = User::from_userid($userid);
      $name   = $user->fullname();
      $answer = $response['free_text']??null;
      if($answer) {
        echo "<tr><td class='name'>$name:</td><td class='response'>$answer</td></tr>";
      }
    }
    echo "</table></div>";
  }

  private function add_select_one_responses($question,$responses)
  {
    $qid = $question['id'];
    $options = $this->questions[$qid]['options'];
    $other   = $this->questions[$qid]['other'];

    if($other) { $options[] = 0; }

    foreach($options as $oid) {
      $option = $oid > 0 ? $this->options[$oid] : 'Other';
      $users = array_filter($responses, fn($a) => $a['selected'] === $oid );
      echo "<div class='option' data-id='$oid'>";
      echo "<div class='option-label'>$option</div>";
      if($users) {
        $extra = $oid>0 ? 'resizable-list' : 'table';
        echo "<div class='select one responses $extra'>";
        $qualifiers = [];
        foreach($users as $uid=>$response) {
          $name = User::from_userid($uid)->fullname();
          $qual = $response['qualifier']??null;

          $mark = '';
          if($qual) {
            $mark = 'qual';
            $qualifiers[$name] = $qual;
          }
          echo "<div class='name $mark'>$name</div>";
          if($oid == 0) {
            echo "<div class='other'>".$response['other']."</div>";
          }
        }
        echo "</div>";
        if($qualifiers) {
          echo "<div class='qualifiers'>";
          echo "<table class='qualifiers'>";
          foreach($qualifiers as $name=>$qual) {
            echo "<tr><td class='name'>$name:</td><td class='qual'>$qual</td></tr>";
          }
          echo "</table>";
          echo "</div>";
        }
      }
      else {
        echo "<div class='none'>(nobody)</div>";
      }
      echo "</div>";
    }
  }

  private function add_select_multi_responses($question,$responses)
  {
    $qid = $question['id'];
    $options = $this->questions[$qid]['options'];
    $other   = $this->questions[$qid]['other'];

    if($other) { $options[] = 0; }

    foreach($options as $oid) {
      $option = $oid > 0 ? $this->options[$oid] : 'Other';
      if($oid > 0) {
        $users = array_filter($responses, fn($a) => in_array($oid,$a['options']) );
      } else {
        $users = array_filter($responses, fn($a) => $a['selected'] === $oid );
      }
      echo "<div class='option' data-id='$oid'>";
      echo "<div class='option-label'>$option</div>";
      if($users) {
        $extra = $oid>0 ? 'resizable-list' : 'table';
        echo "<div class='select one responses $extra'>";
        foreach($users as $uid=>$response) {
          $name = User::from_userid($uid)->fullname();
          $qual = $response['qualifier']??null;
          $mark = $qual ? 'qual' : '';
          echo "<div class='name $mark'>$name</div>";
          if($oid == 0) {
            echo "<div class='other'>".$response['other']."</div>";
          }
        }
        echo "</div>";
      }
      else {
        echo "<div class='none'>(nobody)</div>";
      }
      echo "</div>";
    }

    $qualifiers = [];
    foreach($responses as $uid=>$response) {
      $qual = $response['qualifier'] ?? null;
      if($qual) {
        $name = User::from_userid($uid)->fullname();
        $qualifiers[$name] = $qual;
      }
    }
    if($qualifiers) {
      $prompt = $question['qualifier'];
      echo "<div class='qualifiers'>";
//      echo "<div class='option-label'>$prompt</div>";
      echo "<table class='qualifiers'>";
      foreach($qualifiers as $name=>$qual) {
        echo "<tr><td class='name'>$name:</td><td class='qual'>$qual</td></tr>";
      }
      echo "</table>";
      echo "</div>";
    }
  }
}

function add_section_panel($sid,$content,$responses)
{
  $sp = new SectionPanel($sid,$content,$responses);
  $sp->add();
}


