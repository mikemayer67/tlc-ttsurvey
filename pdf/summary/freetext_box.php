<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary/question_box.php'));
require_once(app_file('pdf/summary/table_response_box.php'));

class SummaryFreetextBox extends SummaryQuestionBox
{
  private PDFTextBox $label_box;
  private ?SummaryTableResponseBox $response_box = null;

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

    $qid       = $question['id'];
    $wording   = $question['wording'];
    $responses = $responses['questions'][$qid] ?? [];

    $this->width = $width;
    
    $this->label_box = new PDFTextBox(
      $summaryPDF, $width, $wording, style:'B', size:K_SUMMARY_FONT_LARGE
    );
    $this->height = $this->label_box->getHeight();

    if($responses) {
      $responses = array_map(fn($a) => $a['free_text'], $responses);
      $this->height += self::vgap;
      $this->response_box = new SummaryTableResponseBox(
        $summaryPDF, $width - self::indent, $responses
      );
      $this->height += $this->response_box->getHeight();
    }
  }

  /**
   * Manages the layout of a freetext box and its children
   * @param float $x 
   * @param float $y 
   * @return void
   */
  protected function position(float $x, float $y)
  {
    parent::position($x, $y);
    $this->label_box->position($x,$y);
    $y += $this->label_box->getHeight();

    $this->response_box?->position($x + self::indent, $y + self::vgap);
  }

  /**
   * Renders the content of a SummarySection box
   * @return void 
   */
  protected function render()
  {
    parent::render();
    $this->label_box->render();
    $this->response_box?->render();
  }


//  protected function debug_color(): array { return [0,0,255]; }
}

