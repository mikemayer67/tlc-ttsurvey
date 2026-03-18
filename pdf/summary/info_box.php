<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/summary/question_box.php'));
require_once(app_file('summary/markdown.php'));

class SummaryInfoBox extends SummaryQuestionBox
{
  private PDFTextBox $text_box;

  public const indent = K_QUARTER_INCH;
  /**
   * @param SummaryPDF $summaryPDF 
   * @param float $width 
   * @param array $question 
   * @param null|SummaryQuestionBox $prev 
   * @return void 
   */
  public function __construct(
    SummaryPDF $summaryPDF,
    float $width,
    array $question,
    ?SummaryQuestionBox $prev = null
  ) {
    parent::__construct($summaryPDF,$prev);

    // $qid       = $question['id'];
    // $wording   = $question['wording'];
    // $user_responses = $responses['questions'][$qid] ?? [];

    $this->width = $width;

    $info = strip_markdown($question['info']);
    $this->text_box = new PDFTextBox(
      $summaryPDF, $width, $info, style:'I',size:K_SUMMARY_FONT_LARGE, multi:true
    );

    $this->height = $this->text_box->getHeight();
  }

  /**
   * Always set the info text without indentation
   * @return bool 
   */
  public function resetIndent(): bool
  {
    return true;
  }

  /**
   * Indentation starts after an in-group info box
   * @return float 
   */
  public function incrementIndent(): float
  {
    return self::indent;
  }

  /**
   * Manages the layout of the info text box
   * @param float $x 
   * @param float $y 
   * @return bool 
   */
  protected function position($x, $y) : bool
  {
    parent::position($x,$y);
    return $this->text_box->position($x,$y);
  }

  /**
   * Renders the info text box
   * @return bool 
   */
  protected function render() : bool
  {
    parent::render();
    return $this->text_box->render();
  }

//  protected function debug_color(): array { return [255,0,255]; }
}

