<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary/config.php'));
require_once(app_file('pdf/summary/table_response_box.php'));

class SummarySectionFeedback extends PDFBox
{ 
  private PDFTextBox $label_box;
  private PDFTextBox $feedback_box;
  private ?SummaryTableResponseBox $response_box = null;

  private const indent = K_QUARTER_INCH;
  private const vgap  = 2;

  /**
   * constructor
   * @param SummaryPDF $summaryPDF 
   * @param float $max_width 
   * @param array $section 
   * @param array $responses 
   * @return void 
   */
  public function __construct(SummaryPDF $summaryPDF, float $max_width, array $section, array $responses)
  {
    parent::__construct($summaryPDF);

    $name = $section['name'];
    $feedback = $section['feedback'] ?? null;
    
    $this->width = $max_width;

    $this->top_pad = K_EIGHTH_INCH;
    $this->label_box = new PDFTextBox(
      $summaryPDF, $max_width, "$name feedback: ",
      style:'B',size:K_SUMMARY_FONT_LARGE
    );
    $feedback_width = $max_width - $this->label_box->getWidth();
    
    $this->feedback_box =new PDFTextBox(
      $summaryPDF,$feedback_width,$feedback,K_SANS_SERIF_FONT,size:K_SUMMARY_FONT_LARGE
    );
    $this->height = max($this->feedback_box->getHeight(),$this->label_box->getHeight());

    $sid = $section['section_id'];
    $responses = $responses['sections'][$sid] ?? null;
    if($responses) {
      $this->height += self::vgap;
      $this->response_box = new SummaryTableResponseBox(
        $summaryPDF, $max_width-self::indent,$responses
      );
      $this->height += $this->response_box->getHeight();
    }
  }

  /**
   * Manges the layout of the section feedback box
   * @param float $x 
   * @param float $y 
   * @return void
   */
  protected function position(float $x, float $y)
  {
    parent::position($x,$y);

    $hl = $this->label_box->getHeight();
    $hf = $this->feedback_box->getHeight();
    $dy = ($hl-$hf)/2;

    $this->label_box->position($x,$y+max(0,-$dy));
    $xo = $x + $this->label_box->getWidth();
    $this->feedback_box->position($xo,$y+max(0,$dy));
    $y += max($hl,$hf);

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
    $this->feedback_box->render();
    $this->response_box?->render();
  }

//  protected function debug_color(): array { return [128,0,0]; }
}