<?php
namespace tlc\tts;

use Soap\Sdl;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/alignable_box.php'));
require_once(app_file('pdf/survey/option_box.php'));
require_once(app_file('pdf/survey/enums.php'));

/**
 * A SurveyOtherBox extends SurveyOptionBox by adding an input area
 *   for the user to fill in an option value of their own
 * This input area:
 *   always appears after the label and radio/checkbox
 *   does not factor into the alignment width
 */
class SurveyOtherBox extends SurveyAlignableBox
{
  private SurveyOptionBox $option;
  private float $other_width;

  private float $input_x = 0;
  private float $input_y = 0;
  private float $input_width = 2*K_INCH;
  private float $input_height = K_QUARTER_INCH;

  const hgap = 2;

  /**
   * @param SurveyPDF $surveyPDF 
   * @param float $max_width 
   * @param string $option_label 
   * @param OptionShape $option_shape 
   * @param SurveyJustification $justification 
   * @param int $fontsize (default = K_SURVEY_FONT_MEDIUM)
   * @return void 
   */
  public function __construct(
    SurveyPDF $surveyPDF,
    float $max_width,
    string $label,
    OptionShape $shape,
    SurveyJustification $justification,
    int $fontsize=K_SURVEY_FONT_MEDIUM)
  {
    parent::__construct($surveyPDF,$justification);

    $extra_width = $this->input_width + self::hgap;

    $this->option = new SurveyOptionBox(
      $surveyPDF, $max_width - $extra_width, $label, $shape, $justification, fontsize:$fontsize
    );

    $this->height = max($this->input_height, $this->option->getHeight());
    $this->width = $this->option->getWidth() + $extra_width;
  }

  // The alignment width applies to the option box alone
  //  It should not apply to the other input
  public function getAlignedWidth(): float {
    return $this->option->getAlignedWidth();
  }
  public function setAlignedWidth(float $w) {
    if($this->justification === SurveyJustification::RIGHT) {
      $dw = $w - $this->option->getAlignedWidth();
      if($dw > 0) { $this->width += $dw; }
    }
    $this->option->setAlignedWidth($w);
  }

  /**
   * Manages positioning of an other box and its children
   * @param float $x 
   * @param float $y 
   * @return bool 
   */
  protected function position(float $x, float $y) : bool
  {
    parent::position($x, $y);
    
    if($this->justification === SurveyJustification::LEFT) {
      $this->input_x = $x + self::hgap + $this->option->getWidth();
    } else {
      $this->input_x = $x + self::hgap + $this->option->getAlignedWidth();
    }
    $dy = ($this->input_height - $this->option->getHeight())/2;
    $this->input_y = ($dy<0) ? $y - $dy : $y;

    $this->option->position($x,max($y,$y+$dy));
    return true;
  }

  public function render() : bool
  {
    if (!parent::render()) { return false; }
    if(!$this->option->render()) { return false; }

    $this->ttpdf->setLineWidth(0.2);
    $this->ttpdf->Rect(
      $this->input_x, $this->input_y,
      $this->input_width, $this->input_height,
    );

    return true;
  }
}