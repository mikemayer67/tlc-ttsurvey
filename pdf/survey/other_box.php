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
  private SurveyOptionBox $_option;
  private float $other_width;

  private float $_input_x = 0;
  private float $_input_y = 0;
  private float $_input_width = 2*K_INCH;
  private float $_input_height = K_QUARTER_INCH;

  private float $_gap = 2;

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $max_width 
   * @param string $option_label 
   * @param OptionShape $option_shape 
   * @param SurveyJustification $justification 
   * @return void 
   */
  public function __construct(
    SurveyPDF $tcpdf,
    float $max_width,
    string $label,
    OptionShape $shape,
    SurveyJustification $justification)
  {
    parent::__construct($tcpdf,$justification);

    $extra_width = $this->_input_width + $this->_gap;

    $this->_option = new SurveyOptionBox(
      $tcpdf, $max_width - $extra_width, $label, $shape, $justification
    );

    $this->_height = max($this->_input_height, $this->_option->getHeight());
    $this->_width = $this->_option->getWidth() + $extra_width;
  }

  // The alignment width applies to the option box alone
  //  It should not apply to the other input
  public function getAlignedWidth(): float {
    return $this->_option->getAlignedWidth();
  }
  public function setAlignedWidth(float $w) {
    if($this->_justification === SurveyJustification::RIGHT) {
      $dw = $w - $this->_option->getAlignedWidth();
      if($dw > 0) { $this->_width += $dw; }
    }
    $this->_option->setAlignedWidth($w);
  }

  protected function position( float $x, float $y)
  {
    parent::position($x, $y);
    
    if($this->_justification === SurveyJustification::LEFT) {
      $this->_input_x = $x + $this->_gap + $this->_option->getWidth();
    } else {
      $this->_input_x = $x + $this->_gap + $this->_option->getAlignedWidth();
    }
    $dy = ($this->_input_height - $this->_option->getHeight())/2;
    $this->_input_y = ($dy<0) ? $y - $dy : $y;

    $this->_option->position($x,max($y,$y+$dy));
  }

  public function render() : bool
  {
    if(!$this->_option->render()) { return false; }

    $this->_tcpdf->setLineWidth(0.2);
    $this->_tcpdf->Rect(
      $this->_input_x, $this->_input_y,
      $this->_input_width, $this->_input_height,
    );

    return true;
  }
}