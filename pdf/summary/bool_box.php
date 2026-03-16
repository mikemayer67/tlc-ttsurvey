<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/summary/question_box.php'));

class SummaryBoolBox extends SummaryQuestionBox
{
  /**
   * @param SummaryPDF $summaryPDF 
   * @param float $width 
   * @param array $question 
   * @param array $responses 
   * @param null|SummaryQuestionBox $prev 
   * @return void 
   */
  public function __construct(
    SummaryPDF $summaryPDF,
    float $width,
    array $question,
    array $responses,
    ?SummaryQuestionBox $prev = null
  ) {
    parent::__construct($summaryPDF,$prev);

    // $qid       = $question['id'];
    // $wording   = $question['wording'];
    // $user_responses = $responses['questions'][$qid] ?? [];

    $this->width = $width;
    $this->height = K_HALF_INCH;
  }

  protected function debug_color(): array { return [255,0,0]; }
}

