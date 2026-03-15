<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary/config.php'));

class SummarySectionHeader extends PDFBox
{
  private string $name;
  private ?PDFBox $name_box = null;

  /**
   * constructor
   * @param SummaryPDF $summaryPDF 
   * @param float $width 
   * @param array $section 
   * @return void 
   */
  public function __construct(SummaryPDF $summaryPDF, float $width, array $section)
  {
    parent::__construct($summaryPDF);

    $this->name = $section['name'];
    $collapsible = $section['collapsible'] ?? true;

    $this->width = $width;
    $this->height = 0;
    
    if ($collapsible) {
      $this->top_pad    = 0;
      $this->bottom_pad = K_QUARTER_INCH;
      $this->name_box = new PDFTextBox(
        $summaryPDF, $width, $this->name, 
        style:'B', size:K_SUMMARY_FONT_X_LARGE
      );
      $this->height += $this->name_box->getHeight();
    } else {
      $this->top_pad = 0;
      $this->bottom_pad = 0;
    }
  }

  protected function isNewPage() : bool
  {
    return true;
  }

  /**
   * Overrides the maxPagePos method.
   *   Section boxes should start in the two 2/3 of the page.
   * @return float 
   */
  public function maxPagePos(): float
  {
    return 0.0;
  } 

  protected function currentSection() : ?string
  {
    return $this->name;
  }

  /**
   * Manges the layout of the section header box
   * @param float $x 
   * @param float $y 
   * @return bool 
   */
  protected function position(float $x, float $y) : bool
  {
    parent::position($x, $y);

    if ($this->name_box) {
      $this->name_box->position($x, $y);
      $y += $this->name_box->getHeight();
    }
    return true;
  }

  /**
   * Renders the content of a SummarySection box
   * @return bool 
   */
  protected function render(): bool
  {

    if (!parent::render()) { return false; }
    if ($this->name_box) {
      if (!$this->name_box->render()) { return false; }
    }
    return true;
  }
}