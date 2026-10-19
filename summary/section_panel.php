<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

class SectionPanel
{
  private $sid       = null;
  private $questions = null;
  private $options   = null;
  private $responses = null;

  private $indent    = false;
  private $grouped   = false;

  function __construct($sid,$content,$responses)
  {
    $this->sid       = $sid;
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

    todo("Update the following as question.grouped no longer exists");
    $this->indent = false;
    foreach($this->questions as $question) {
      if($question['grouped'] === 'NO') {
        $this->grouped = false;
        $this->indent = false;
      } else {
        $this->grouped = true;
        // don't start indentation unless info text is found
      }
      $this->add_question($question);
    }

    echo "</div>";
  }

  private function add_question($question)
  {
    $type = $question['type']??null;

    if($type->isInfo()) {
      $this->add_info_text($question);
      return;
    }

    $qid     = $question['id'];
    $wording = $question['wording'];
    $indent  = $this->indent ? 'indent' : '';
    $class   = str_replace('_',' ',$type);

    echo "<div class='$class question $indent'>";
    echo "<div class='label'>$wording</div>";

    $responses = $this->responses[$qid] ?? [];
    switch($type) {
      case QuestionType::Bool:
        $this->add_bool_responses($question, $responses);
        break;
      case QuestionType::FreeText:
        $this->add_freetext_responses($question, $responses);
        break;
      case QuestionType::SelectOne: // intentional fallthrough
      case QuestionType::SelectMulti:
        $this->add_select_responses($question, $responses);
        break;
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

    $info_text = strip_markdown($question['info']);
    echo "<div class='info text question'>";
    echo "<div class='label'>$info_text</div>";
    echo "</div>";
  }

  private function add_bool_responses($question,$responses)
  {
    if(!$responses) { return; }

    echo "<div class='bool responses resizable-list'>";
    foreach($responses as $response) {
      if($response['selected']) {
        $userid = $response['userid'];
        $user   = User::from_userid($userid);
        $name   = $user->fullname();
        echo "<div class='name'>$name</div>";
      }
    }
    echo "</div>";

    $this->add_qualifiers($question, $responses);
  }

  private function add_select_responses($question,$responses)
  {
    $type      = $question['type'];
    $options   = $question['options'];
    $has_other = $question['has_other'];
    $other     = $question['other'] ?? 'Other';

    $multi = $type === QuestionType::SelectMulti;

    if($has_other) { $options[] = 0; }

    foreach($options as $oid) {
      $option = $oid > 0 ? $this->options[$oid] : $other;
      if($oid && $multi) {
        $users = array_filter($responses, fn($a) => in_array($oid, $a['options'] ?? []));
      } else {
        $users = array_filter($responses, fn($a) => $a['selected'] === $oid);
      }
      // skip showing "other" if nobody selected that
      if($oid === 0 && !$users) { continue; }

      echo "<div class='option' data-id='$oid'>";
      echo "<div class='option-label'>$option</div>";
      if($users) {
        $format = $oid>0 ? 'resizable-list' : 'table';
        echo "<div class='select one responses $format'>";
        foreach($users as $uid=>$response) {
          $name = User::from_userid($uid)->fullname();

          if($oid) {
            echo "<div class='name'>$name</div>";
          } else {
            echo "<div class='name'>$name</div>";
            echo "<div class='other'>".$response['other']."</div>";
          }
        }
        echo "</div>";
      }
      echo "</div>";
    }

    $this->add_qualifiers($question, $responses);
  }

  private function add_freetext_responses($question,$responses)
  {
    if(!$responses) { return; }

    echo "<div class='freetext responses'>";
    echo "<table>";
    foreach($responses as $response) {
      $userid = $response['userid'];
      $user   = User::from_userid($userid);
      $name   = $user->fullname();
      $answer = $response['freetext']??null;
      if($answer) {
        echo "<tr><td class='name'>$name:</td><td class='response'>$answer</td></tr>";
      }
    }
    echo "</table></div>";
  }
  
  private function add_qualifiers($question, $responses)
  {
    $qualifiers = array_filter($responses, fn($a) => $a['qualifier']);
    $label = $question['qualifier'] ?? '';
    if ($qualifiers) {
      echo "<div class='qualifiers'>";
      echo "<div class='qualifier text'>$label</div>";
      echo "<table class='qualifiers'>";
      foreach ($qualifiers as $userid => $response) {
        $name = User::from_userid($userid)->fullname();
        $qual = $response['qualifier'];
        echo "<tr><td class='name'>$name:</td><td class='qual'>$qual</td></tr>";
      }
      echo "</table>";
      echo "</div>";
    }
  }
}