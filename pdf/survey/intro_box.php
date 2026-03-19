<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/config.php'));
require_once(app_file('survey/markdown.php'));

class SurveyIntroBox extends PDFBox
{
  private PDFBox $box;

  /**
   * @param SurveyPDF $surveyPDF 
   * @param float $max_width 
   * @param string $intro 
   * @param int $fontsize (default = K_SURVEY_FONT_MEDIUM)
   * @return void 
   */
  public function __construct(SurveyPDF $surveyPDF, float $max_width, string $intro, int $fontsize=K_SURVEY_FONT_MEDIUM)
  {
    parent::__construct($surveyPDF);

    if (possibleMarkdown($intro)) {
      $this->box = new PDFMarkdownBox($surveyPDF,$max_width,$intro,size:$fontsize);
    } else {
      $this->box = new PDFTextBox($surveyPDF,$max_width,$intro,size:$fontsize,multi:true);
    }
    $this->width = $max_width;
    $this->height = $this->box->getHeight();
  }

  public function incrementIndent(): float { return K_QUARTER_INCH; }

  /**
   * Manages positioning of a intro box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return bool 
   */
  protected function position(float $x, float $y) : bool
  {
    parent::position($x, $y);
    $this->box->position($x,$y);
    return true;
  }

  /**
   *
   * @return void
   */
  public function render()
  {
    parent::render();
    $this->box->render();
  }

  protected function debug_color(): array { return [0,0,255]; }
}