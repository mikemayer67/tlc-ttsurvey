<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary/config.php'));

class SummarySectionHeader extends PDFBox
{
  private string $name;
  private ?PDFBox $name_box = null;

  private const indent = -K_QUARTER_INCH;

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
        style:'B', size:K_SUMMARY_FONT_XX_LARGE
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
   * @return void
   */
  protected function position(float $x, float $y)
  {
    parent::position($x, $y);
    $this->name_box?->position($x+self::indent, $y);
  }

  /**
   * Renders the content of a SummarySection box
   * @return void 
   */
  protected function render()
  {
    parent::render();
    $this->name_box?->render();
  }
}