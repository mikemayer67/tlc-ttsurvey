<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/alignable_box.php'));
require_once(app_file('pdf/survey/enums.php'));
require_once(app_file('pdf/survey/config.php'));

/**
 * A SurveyOptionBox contains a label and either a radio button or a checkbox
 * - It is an alignable box so that it can be made to align with other
 *   alignable boxes.
 * - It can be justified left or right.
 *   - left:  radio/checkbox will appear before label
 *   - right: radio/checkbox will appear after label
 */
class SurveyOptionBox extends SurveyAlignableBox
{
  private PDFTextBox $label;

  private float $input_x = 0;
  private float $input_y = 0;
  private float $input_width  = K_EIGHTH_INCH;
  private float $input_height = K_EIGHTH_INCH;
  private float $input_radius = 0;

  private float $gap = 1;

  /**
   * @param SurveyPDF $surveyPDF 
   * @param float $max_width 
   * @param string $label 
   * @param OptionShape $shape 
   * @param SurveyJustification $justification 
   * @param int $fontsize (default=K_SURVEY_FONT_MEDIUM)
   * @return void 
   */
  public function __construct(
    SurveyPDF $surveyPDF, 
    float $max_width,
    string $label,
    OptionShape $shape, 
    SurveyJustification $justification,
    int $fontsize = K_SURVEY_FONT_MEDIUM )
  {
    parent::__construct($surveyPDF,$justification);

    $max_width -= $this->input_width + $this->gap;
    $this->label = new PDFTextBox($surveyPDF,$max_width,$label,size:$fontsize);

    $size = min($this->input_width, $this->input_height);
    switch($shape) {
      case OptionShape::RADIO:    $this->input_radius = $size/2; break;
      case OptionShape::CHECKBOX: $this->input_radius = $size/4; break;
    }

    $this->width  = $this->input_width + $this->gap + $this->label->getWidth();
    $this->height = max($this->input_height, $this->label->getHeight());
    $this->aligned_width = $this->width;
  }

  /**
   * Manages positioning of an option box and its children
   * @param float $x 
   * @param float $y 
   * @return bool 
   */
  protected function position(float $x, float $y) : bool
  {
    parent::position($x, $y);

    if($this->justification === SurveyJustification::LEFT) {
      $xc = $x;
      $xw = $x + $this->input_width + $this->gap;
    } else {
      $xc = $x + $this->aligned_width - $this->input_width;
      $xw = $xc - $this->gap - $this->label->getWidth();
    }

    $dy = ($this->input_height - $this->label->getHeight()) / 2;
    $yw = ($dy>0) ? $y + $dy : $y;
    $yc = ($dy<0) ? $y - $dy : $y;

    $this->label->position($xw, $yw);
    $this->input_x = $xc;
    $this->input_y = $yc;
    return true;
  }
  
  public function render(): bool
  {
    if (!parent::render()) { return false; }
    if(!$this->label->render()) { return false; }
    
    $this->ttpdf->setLineWidth(0.2);
    $this->ttpdf->RoundedRect(
      $this->input_x, $this->input_y,
      $this->input_width, $this->input_height,
      $this->input_radius
    );
    return true;
  }

}

