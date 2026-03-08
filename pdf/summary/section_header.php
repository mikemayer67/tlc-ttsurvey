<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary/config.php'));

class SummarySectionHeader extends PDFBox
{
  private string $_name;
  private ?PDFBox $_name_box = null;

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

    $this->_name = $section['name'];
    $collapsible = $section['collapsible'] ?? true;

    $this->_width = $width;
    $this->_height = 0;
    
    if ($collapsible) {
      $this->_top_pad    = 0;
      $this->_bottom_pad = K_QUARTER_INCH;
      $this->_name_box = new PDFTextBox(
        $summaryPDF, $width, $this->_name, 
        K_SANS_SERIF_FONT, 'B', K_SUMMARY_FONT_X_LARGE
      );
      $this->_height += $this->_name_box->getHeight();
    } else {
      $this->_top_pad = 0;
      $this->_bottom_pad = 0;
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
    return $this->_name;
  }

  /**
   * Manges the layout of the section header box
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position(float $x, float $y)
  {
    parent::position($x, $y);

    if ($this->_name_box) {
      $this->_name_box->position($x, $y);
      $y += $this->_name_box->getHeight();
    }
  }

  /**
   * Renders the content of a SummarySection box
   * @return bool 
   */
  protected function render(): bool
  {

    if (!parent::render()) { return false; }
    if ($this->_name_box) {
      if (!$this->_name_box->render()) { return false; }
      $y = $this->_name_box->_y + $this->_name_box->getHeight();
    }
    return true;
  }
}