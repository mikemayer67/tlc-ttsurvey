<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/alignable_box.php'));
require_once(app_file('pdf/survey/option_box.php'));
require_once(app_file('pdf/survey/intro_box.php'));
require_once(app_file('pdf/survey/qualifier_box.php'));
require_once(app_file('pdf/survey/option_box.php'));
require_once(app_file('pdf/survey/enums.php'));

class SurveyBoolBox extends SurveyAlignableBox 
{
  private SurveyOptionBox     $input;
  private ?SurveyIntroBox     $intro_box = null;
  private ?SurveyQualifierBox $qual_box  = null;

  private float $vpad = 0;

  /**
   * @param SurveyPDF $surveyPDF 
   * @param float $max_width 
   * @param array $question 
   * @return void 
   */
  public function __construct(SurveyPDF $surveyPDF, float $max_width, array $question)
  {
    parent::__construct($surveyPDF);

    $intro   = $question['intro'] ?? null;
    $wording = $question['wording'];
    $layout  = $question['layout' ?? 'LEFT'];
    $qual    = $question['qualifier'] ?? null;

    if($intro) {
      $this->intro_box = new SurveyIntroBox($surveyPDF,$max_width,$intro);
      $max_width     -= $this->intro_box->incrementIndent();
      $this->height += $this->intro_box->getHeight();
      $this->width   = max($this->width, $this->intro_box->getWidth());
      $this->vpad    = 3;
    }

    $this->input = new SurveyOptionBox(
      $surveyPDF, $max_width, $wording, 
      OptionShape::CHECKBOX,
      SurveyJustification::fromInput($layout),
    );
    $this->height += $this->input->getHeight();
    $this->width   = max($this->width, $this->input->getWidth());
    $this->aligned_width = $this->input->getAlignedWidth();

    if($qual) {
      $this->qual_box = new SurveyQualifierBox($surveyPDF,$max_width,$qual);
      $this->height += $this->qual_box->getHeight();
      $this->width   = max($this->width, $max_width);
      $this->vpad    = 3;
    }

    $this->height += 2*$this->vpad;
  }

  // The alignment width applies to the input box alone
  //  It should not apply to the intro or qualifier boxes
  public function getAlignedWidth(): float {
    return $this->input->getAlignedWidth();
  }
  public function setAlignedWidth(float $w) {
    $this->input->setAlignedWidth($w);
  }

  /**
   * Manages positioning of a bool box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return bool 
   */
  protected function position(float $x, float $y) : bool
  {
    parent::position($x, $y);
    $y += $this->vpad;

    // add (optional) intro box
    if($this->intro_box) {
      $this->intro_box->position($x,$y);
      $y += $this->intro_box->getHeight();
      $x += $this->intro_box->incrementIndent();
    }

    $this->input->position($x,$y);
    $y += $this->input->getHeight();

    // add (optional) qual box
    if($this->qual_box) {
      $this->qual_box->position($x+K_QUARTER_INCH,$y);
    }
    return true;
  }

  public function render(): bool
  {
    if (!parent::render()) { return false; }
    return (
      $this->input->render() &&
      ($this->intro_box?->render() ?? true) &&
      ($this->qual_box?->render() ?? true)
    );
  }
}
