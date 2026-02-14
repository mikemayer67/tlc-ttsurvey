<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('survey/markdown.php'));

class SurveySectionBox extends PDFBox
{
  private ?PDFBox $_name_box = null;
  private ?PDFBox $_intro_box = null;

  private float $_gap = 1; // mm

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $box_width
   * @param array $section 
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $width, array $section)
  {
    parent::__construct($tcpdf);

    $name        = $section['name'];
    $collapsible = $section['collapsible'] ?? true;
    $intro       = $section['intro'] ?? '';

    $this->_width = $width;
    $this->_height = 0;

    if ($collapsible || $intro) {
      $this->_top_pad    = 0.25 * K_INCH;
      $this->_bottom_pad = 0.125 * K_INCH;
    } else {
      $this->_top_pad = 0;
      $this->_bottom_pad = 0;
    }

    if ($collapsible) {
      $this->_name_box = new PDFTextBox($tcpdf, $width, $name, K_SERIF_FONT, size: 16);
      $this->_height += $this->_name_box->getHeight();
    }
    if ($collapsible && $intro) {
      $this->_height += $this->_gap;
    }
    if ($intro) {
      if (possibleMarkdown($intro)) {
        $this->_intro_box = new PDFMarkdownBox($tcpdf, $width, $intro, size: 9);
      } else {
        $this->_intro_box = new PDFTextBox($tcpdf, $width, $intro, style: 'I', size: 9, multi: true);
      }
      $this->_height += $this->_intro_box->getHeight();
    }
  }

  /**
   * Overrides the maxPagePos method.
   *   Section boxes should start in the two 2/3 of the page.
   * @return float 
   */
  public function maxPagePos(): float
  {
    return 0.67;
  }

  /**
   * Section boxes always reset the indent to 0
   * @return bool 
   */
  public function resetIndent(): bool
  {
    return true;
  }

  /**
   * Section boxes increase the indent for subsequent boxes if it contains an intro box
   * @return float amount by which to incrment the indent
   */
  public function incrementIndent(): float
  {
    return $this->_intro_box ? K_QUARTER_INCH : 0;
  }

  /**
   * Manages the layout of the section box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function layout(int $page, float $x, float $y)
  {
    parent::layout($page,$x,$y);

    if ($this->_name_box) {
      $this->_name_box->layout($page, $x, $y);
      $y += $this->_name_box->getHeight();
      if ($this->_intro_box) { $y += $this->_gap; }
    }
    if ($this->_intro_box) {
      $this->_intro_box->layout($page, $x, $y);
    }
  }

  /**
   * Renders the content of a SurveySection box
   * @return bool 
   */
  protected function render(): bool
  {
    if ($this->_name_box) {
      if (!$this->_name_box->render()) { return false; }
      $y = $this->_name_box->_y + $this->_name_box->getHeight();
      $this->_tcpdf->setLineWidth(0.2);
      $x1 = PDF_MARGIN_LEFT;
      $x2 = $this->_tcpdf->getPageWidth() - PDF_MARGIN_RIGHT;
      $this->_tcpdf->Line($x1, $y, $x2, $y);
    }

    if ($this->_intro_box) {
      if (!$this->_intro_box->render()) { return false; }
    }

    return true;
  }
}
