<?php
namespace tlc\tts;

use TCPDF;

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
  private PDFTextBox $_label;

  private float $_input_x = 0;
  private float $_input_y = 0;
  private float $_input_width  = K_EIGHTH_INCH;
  private float $_input_height = K_EIGHTH_INCH;
  private float $_input_radius = 0;

  private float $_gap = 1;

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $max_width 
   * @param string $label 
   * @param OptionShape $shape 
   * @param SurveyJustification $justification 
   * @param int $fontsize (default=K_SURVEY_FONT_MEDIUM)
   * @return void 
   */
  public function __construct(
    SurveyPDF $tcpdf, 
    float $max_width,
    string $label,
    OptionShape $shape, 
    SurveyJustification $justification,
    int $fontsize = K_SURVEY_FONT_MEDIUM )
  {
    parent::__construct($tcpdf,$justification);

    $max_width -= $this->_input_width + $this->_gap;
    $this->_label = new PDFTextBox($tcpdf,$max_width,$label,size:$fontsize);

    $size = min($this->_input_width, $this->_input_height);
    switch($shape) {
      case OptionShape::RADIO:    $this->_input_radius = $size/2; break;
      case OptionShape::CHECKBOX: $this->_input_radius = $size/4; break;
    }

    $this->_width  = $this->_input_width + $this->_gap + $this->_label->getWidth();
    $this->_height = max($this->_input_height, $this->_label->getHeight());
    $this->_aligned_width = $this->_width;
  }

  protected function position( float $x, float $y)
  {
    parent::position($x, $y);

    if($this->_justification === SurveyJustification::LEFT) {
      $xc = $x;
      $xw = $x + $this->_input_width + $this->_gap;
    } else {
      $xc = $x + $this->_aligned_width - $this->_input_width;
      $xw = $xc - $this->_gap - $this->_label->getWidth();
    }

    $dy = ($this->_input_height - $this->_label->getHeight()) / 2;
    $yw = ($dy>0) ? $y + $dy : $y;
    $yc = ($dy<0) ? $y - $dy : $y;

    $this->_label->position($xw, $yw);
    $this->_input_x = $xc;
    $this->_input_y = $yc;
  }
  
  public function render(): bool
  {
    if (!parent::render()) { return false; }
    if(!$this->_label->render()) { return false; }
    
    $this->_tcpdf->setLineWidth(0.2);
    $this->_tcpdf->RoundedRect(
      $this->_input_x, $this->_input_y,
      $this->_input_width, $this->_input_height,
      $this->_input_radius
    );
    return true;
  }

}

