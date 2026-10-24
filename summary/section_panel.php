<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

class SectionPanel 
{
  private int            $section_id;
  private SummaryContent $content;

  public function __construct(int $section_id, SummaryContent $content)
  {
    $this->section_id = $section_id;
    $this->content    = $content;
  }

  /**
   * Adds a section panel to the summary page
   * @param int $section_id 
   * @param SummaryContent $content 
   */
  public function add_to_summary() : void
  {
    $sid = $this->section_id;
    echo "<div id='panel-$sid' class='panel panel-$sid'>";

    $groups = $this->content->question_groups($sid);

    foreach( $groups as $questions)
    {
      $indent = false;
      foreach($questions as $question) {
        $responses = $this->content->responses($question->id());
        $indent = $this->add_question($question,$indent);
      }
    }
    echo "</div>";
  }

  private function add_question(Question $question, bool $indent) : bool
  {
    if( $question instanceof InfoQuestion ) {
      $this->add_info_responses($question);
      return true;
    }

    assert($question instanceof QuestionHasWording);

    $wording = $question->wording();
    $classes = ['question'];
    $classes[] = str_replace('_',' ',strtolower($question->type()->value));
    if($indent) { $classes[] = 'indent'; }
    $classes = implode(' ',$classes);

    echo "<div class='$classes'>";
    echo "<div class='label'>$wording</div>";

    switch($question->type())
    {
      case QuestionType::FreeText:
        $this->add_freetext_responses($question);
        break;

      case QuestionType::Bool:
        $this->add_bool_responses($question);
        break;

      case QuestionType::SelectOne:
      case QuestionType::SelectMulti:
        $this->add_select_responses($question);
        break;
    }

    echo "</div>";

    return $indent;
  }

  private function add_info_responses(InfoQuestion $question): void
  {
    $info_text = strip_markdown($question->info_text());
    echo "<div class='info text question'>";
    echo "<div class='label'>$info_text</div>";
    echo "</div>";
  }

  function add_bool_responses(BoolQuestion $question) : void
  {
    $qid = $question->id();

    $responses = $this->content->responses($qid);

    echo "<div class='bool responses resizable-list'>";
    foreach ($responses as $response) {
      if ($response['selected']) {
        $userid = $response['userid'];
        $user   = User::from_userid($userid);
        $name   = $user->fullname();
        echo "<div class='name'>$name</div>";
      }
    }
    echo "</div>";

    if ($question->has_qualifier()) {
      $this->add_qualifiers($question->qualifier(), $responses);
    }
  }

  private function add_freetext_responses(FreeTextQuestion $question)
  {
    $qid = $question->id();
    $responses = $this->content->responses($qid);

    $responses = array_filter(
      array_map(fn($a) => $a['freetext'] ?? null, $responses)
    );

    echo "<div class='freetext responses'>";
    echo "<table>";
    foreach ($responses as $userid => $response) {
      $user   = User::from_userid($userid);
      $name   = $user->fullname();
      echo "<tr><td class='name'>$name:</td><td class='response'>$response</td></tr>";
    }
    echo "</table></div>";
  }



  function add_select_responses(SelectQuestion $question) : void
  {
    $question_id = $question->id();
    $options     = $question->options();
    $is_multi    = $question->is_multi();

    $responses   = $this->content->responses($question_id);

    foreach($options as $option_id) 
    {
      // determine option text associated with current option id
      $option = $this->content->option($option_id);

      // find all participants who selected this option
      if($is_multi) {
        $userids = array_keys(
          array_filter($responses, fn($r) => in_array($option_id, $r['options']??[]))
        );
      } else {
        $userids = array_keys(
          array_filter($responses, fn($r) => $r['selected'] === $option_id)
        );
      }

      echo "<div class='option' data-id='$option_id'>";
      echo "<div class='option-label'>$option</div>";
      if($userids) {
        echo "<div class='select one responses resizable-list'>";
        foreach($userids as $userid) {
          $name = User::from_userid($userid)->fullname();
          echo "<div class='name'>$name</div>";
        }
        echo "</div>";
      }
      echo "</div>";
    }

    if($question->has_other())
    {
      $prompt = $question->other_prompt();

      // for both single and multi select, if user selected 'other', then the 
      //   selected field of response will be set to 0
      $userids = array_keys(
        array_filter($responses, fn($r) => $r['selected']===0)
      );
      // skip showing "other" responses if nobody selected this option
      if($userids) {
        echo "<div class='option' data-id='0'>";
        echo "<div class='option-label'>$prompt</div>";
        echo "<div class='select one responses table'>";
        foreach($userids as $userid) {
          $name = User::from_userid($userid)->fullname();
          $option = $responses[$userid]['other'] ?? '<blank>';
          echo "<div class='name'>$name</div>";
          echo "<div class='other'>$option</div>";
        }
        echo "</div></div>";
      }
    }

    if($question->has_qualifier()) {
      $this->add_qualifiers($question->qualifier(), $responses);
    }
  }


  private function add_qualifiers(string $qualifier, array $responses)
  {
    $responses = array_filter(
      array_map( fn($a) => $a['qualifier'] ?? null, $responses)
    );

    if($responses) 
    {
      echo "<div class='qualifiers'>";
      echo "<div class='qualifier text'>$qualifier</div>";
      echo "<table class='qualifiers'>";
      foreach ($responses as $userid => $response) {
        $name = User::from_userid($userid)->fullname();
        echo "<tr><td class='name'>$name:</td><td class='qual'>$response</td></tr>";
      }
      echo "</table>";
      echo "</div>";
    }
  }
}


/**
 * Adds a section panel to the summary page
 * @param int $section_id 
 * @param SummaryContent $content 
 */
function add_section_panel(int $section_id, SummaryContent $content) : void
{
  $sp = new SectionPanel($section_id, $content);
  $sp->add_to_summary();
}