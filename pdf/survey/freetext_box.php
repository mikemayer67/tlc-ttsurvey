<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/intro_box.php'));
require_once(app_file('pdf/survey/config.php'));

class SurveyFreetextBox extends PDFBox
{
  private ?SurveyIntroBox $intro_box = null;
  private PDFBox          $wording_box;

  private array $entry_box = [0,0,0,3*K_QUARTER_INCH];
  const vgap = 1; // mm

  /**
   * @param SurveyPDF $surveyPDF 
   * @param float $max_width 
   * @param array $question 
   * @return void 
   */
  public function __construct(SurveyPDF $surveyPDF, float $max_width, array $question)
  {
    parent::__construct($surveyPDF);

    $wording = $question['wording'];
    $intro   = $question['intro'] ?? null;

    $this->width = $max_width;
    $this->height = 0;

    if($intro) {
      $this->intro_box = new SurveyIntroBox($surveyPDF,$max_width,$intro);
      $max_width     -= $this->intro_box->incrementIndent();
      $this->height += $this->intro_box->getHeight();
    }

    $this->wording_box = new PDFTextBox($surveyPDF,$max_width,$wording,size:K_SURVEY_FONT_MEDIUM);
    $this->height += $this->wording_box->getHeight();

    $this->entry_box[2] = $max_width;
    $this->height += $this->entry_box[3];
  }

  /**
   * Manages positioning of a free text box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position( float $x, float $y)
  {
    parent::position($x, $y);

    if($this->intro_box) {
      $this->intro_box->position($x, $y);
      $y += $this->intro_box->getHeight() + self::vgap;
      $x += $this->intro_box->incrementIndent();
    }

    $this->wording_box->position($x, $y);
    $y += $this->wording_box->getHeight();

    $this->entry_box[0] = $x;
    $this->entry_box[1] = $y;
  }

  /**
   * Renders the content of a free text box
   * @return bool 
   */
  protected function render() : bool
  {
    if (!parent::render()) { return false; }
    $box = $this->intro_box;
    if($box) {
      if(!$box->render()) { return false; }
    }
    $box = $this->wording_box;
    if(!$box->render()) { return false; }

    //$x1 = $this->entry_box[0];
    //$y1 = $this->entry_box[1];
    //$x2 = $x1 + $this->entry_box[2];
    //$y2 = $y1 + $this->entry_box[3];
    //$this->ttpdf->Line($x1,$y2,$x2,$y2);
    
    //$this->ttpdf->Rect(...$this->entry_box);

    return true;
  }
}
