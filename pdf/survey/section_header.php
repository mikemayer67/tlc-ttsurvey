<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/config.php'));
require_once(app_file('survey/markdown.php'));

class SurveySectionHeader extends PDFBox
{
  private ?PDFBox $name_box = null;
  private ?PDFBox $intro_box = null;

  private const vgap = 1; // mm

  /**
   * constructor
   * @param SurveyPDF $surveyPDF 
   * @param float $width 
   * @param array $section 
   * @return void 
   */
  public function __construct(SurveyPDF $surveyPDF, float $width, array $section)
  {
    parent::__construct($surveyPDF);

    $name        = $section['name'];
    $collapsible = $section['collapsible'] ?? true;
    $intro       = $section['intro'] ?? '';

    $this->width = $width;
    $this->height = 0;

    if ($collapsible || $intro) {
      $this->top_pad    = 0.25 * K_INCH;
      $this->bottom_pad = 0.125 * K_INCH;
    } else {
      $this->top_pad = 0;
      $this->bottom_pad = 0;
    }

    if ($collapsible) {
      $this->name_box = new PDFTextBox($surveyPDF, $width, $name, K_SERIF_FONT, size:K_SURVEY_FONT_X_LARGE);
      $this->height += $this->name_box->getHeight();
    }
    if ($collapsible && $intro) {
      $this->height += self::vgap;
    }
    if ($intro) {
      if (possibleMarkdown($intro)) {
        $this->intro_box = new PDFMarkdownBox($surveyPDF, $width, $intro, size:K_SURVEY_FONT_SMALL);
      } else {
        $this->intro_box = new PDFTextBox($surveyPDF, $width, $intro, style:'I', size:K_SURVEY_FONT_SMALL, multi:true);
      }
      $this->height += $this->intro_box->getHeight();
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
    return $this->intro_box ? K_QUARTER_INCH : 0;
  }

  /**
   * Manages the layout of the section box and its children
   * @param float $x 
   * @param float $y 
   * @return void
   */
  protected function position(float $x, float $y)
  {
    parent::position($x,$y);

    $this->name_box?->position($x, $y);

    if($this->name_box && $this->intro_box) {
      $y += self::vgap + $this->name_box->getHeight();
    }

    $this->intro_box?->position($x, $y);
  }

  /**
   * Renders the content of a SurveySection box
   * @return void 
   */
  protected function render()
  {
    parent::render();

    if($this->name_box) {
      $this->name_box->render();

      $y = $this->name_box->y + $this->name_box->getHeight();
      $x1 = PDF_MARGIN_LEFT;
      $x2 = $this->ttpdf->getPageWidth() - PDF_MARGIN_RIGHT;
      $this->ttpdf->setLineWidth(0.2);
      $this->ttpdf->Line($x1, $y, $x2, $y);
    }

    $this->intro_box?->render();
  }
}
