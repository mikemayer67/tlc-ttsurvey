<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('include/users.php'));
require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary/list_response_box.php'));

/**
 * SummaryOptionBox is used to render responders for a single select question option
 * @package tlc\tts
 */
class SummaryOptionBox extends PDFBox
{
  protected PDFBox $label_box;
  protected ?SummaryListResponseBox $response_box = null;

  private const vgap = 1; // mm
  private const indent = K_QUARTER_INCH;

  /**
   * constructor
   * @param SummaryPDF $summaryPDF 
   * @param float $width 
   * @param string $label 
   * @param array $userids 
   * @return void 
   */
  public function __construct(SummaryPDF $summaryPDF, float $width, string $label, array $userids)
  {
    parent::__construct($summaryPDF);

    $this->width = $width;

    $this->label_box = new PDFTextBox(
      $summaryPDF, $width, $label,
      style:'B', size: K_SUMMARY_FONT_MEDIUM,
    );
    $this->height = $this->label_box->getHeight();

    if($userids) {
      $this->response_box = new SummaryListResponseBox( 
        $summaryPDF, $width - self::indent, $userids
      );
      $this->height += self::vgap + $this->response_box->getHeight();
    }
  }

  /**
   * Manages the layout of a summary question option box
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position(float $x, float $y)
  {
    parent::position($x, $y);
    $this->label_box->position($x,$y);
    $y += $this->label_box->getHeight() + self::vgap;
    $x += self::indent;
    $this->response_box?->position($x,$y);
  }

  /**
   * Renders the content of a question option box
   * @return bool 
   */
  protected function render()
  {
    parent::render();
    $this->label_box->render();
    $this->response_box?->render();
  }

  protected function debug_color(): array { return [255,128,0]; }
}