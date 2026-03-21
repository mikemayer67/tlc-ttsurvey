<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/summary/question_box.php'));
require_once(app_file('pdf/summary/list_response_box.php'));
require_once(app_file('pdf/summary/qualifiers_box.php'));

class SummaryBoolBox extends SummaryQuestionBox
{
  private PDFTextBox $label_box;
  private ?SummaryListResponseBox $response_box = null;
  private ?SummaryQualifiersBox   $qualifiers_box = null;

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
      $summaryPDF,
      $width,
      $wording,
      style: 'B',
      size: K_SUMMARY_FONT_LARGE
    );
    $this->height = $this->label_box->getHeight();

    if ($responses) {
      $responders = [];
      $qualifiers = [];
      foreach ($responses as $response) {
        $userid    = $response['userid'];
        $selected  = $response['selected'] ?? false;
        $qualifier = $response['qualifier'] ?? '';

        if ($response['selected']) {
          $responders[]        = $userid;
        }
        if ($qualifier) {
          $qualifiers[$userid] = $qualifier;
        }
      }
    }

    if ($responders) {
      $this->height += self::vgap;
      $this->response_box = new SummaryListResponseBox(
        $summaryPDF,
        $width - self::indent,
        $responders
      );
      $this->height += $this->response_box->getHeight();
    }

    if($responders && $qualifiers) { $this->height += self::vgap; }

    if ($qualifiers) {
      $this->height += self::vgap;
      $this->qualifiers_box = new SummaryQualifiersBox(
        $summaryPDF,
        $width - self::indent,
        $question['qualifier'],
        $qualifiers
      );
      $this->height += $this->qualifiers_box->getHeight();
    }
  }

  /**
   * Manages the layout of a bool box and its children
   * @param float $x 
   * @param float $y 
   * @return void
   */
  protected function position(float $x, float $y)
  {
    parent::position($x, $y);
    $this->label_box->position($x,$y);
    $y += $this->label_box->getHeight();

    if($this->response_box) {
      $y += self::vgap;
      $this->response_box->position($x + self::indent, $y);
      $y += $this->response_box->getHeight();
    }

    if($this->response_box && $this->qualifiers_box) { $y += self::vgap; }

    if($this->qualifiers_box) {
      $y += self::vgap;
      $this->qualifiers_box->position($x + self::indent, $y);
      $y += $this->qualifiers_box->getHeight();
    }
  }

  /**
   * Renders the content of a bool box
   * @return void 
   */
  protected function render()
  {
    parent::render();
    $this->label_box->render();
    $this->response_box?->render();
    $this->qualifiers_box?->render();
  }

  protected function debug_color(): array { return [255,0,0]; }
}

