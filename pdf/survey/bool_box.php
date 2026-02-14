<?php
namespace tlc\tts;

use COM;

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
  private PDFBox              $_wording_box;
  private SurveyOptionBox     $_input;
  private ?SurveyIntroBox     $_intro_box = null;
  private ?SurveyQualifierBox $_qual_box  = null;

  private float $_padding = 0;

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $max_width 
   * @param array $question 
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $max_width, array $question)
  {
    parent::__construct($tcpdf);

    $intro   = $question['intro'] ?? null;
    $wording = $question['wording'];
    $layout  = $question['layout' ?? 'LEFT'];
    $qual    = $question['qualifier'] ?? null;

    if($intro) {
      $this->_intro_box = new SurveyIntroBox($tcpdf,$max_width,$intro);
      $max_width -= $this->_intro_box->incrementIndent();
      $this->_height += $this->_intro_box->getHeight();
      $this->_width = $max_width;
      $this->_padding = 3;
    }

    $this->_input = new SurveyOptionBox(
      $tcpdf, $max_width, $wording, 
      OptionShape::CHECKBOX,
      SurveyJustification::fromInput($layout),
    );
    $this->_height += $this->_input->getHeight();
    $this->_width = max($this->_width, $this->_input->getWidth());
    $this->_aligned_width = $this->_input->getAlignedWidth();

    if($qual) {
      $this->_qual_box = new SurveyQualifierBox($tcpdf,$max_width,$qual);
      $this->_height += $this->_qual_box->getHeight();
      $this->_width = max($this->_width, $max_width);
      $this->_padding = 3;
    }

    $this->_height += 2*$this->_padding;
  }

  // The alignment width applies to the input box alone
  //  It should not apply to the intro or qualifier boxes
  public function getAlignedWidth(): float {
    return $this->_input->getAlignedWidth();
  }
  public function setAlignedWidth(float $w) {
    $this->_input->setAlignedWidth($w);
  }

  /**
   * Manages layout of a bool box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function layout(int $page, float $x, float $y)
  {
    parent::layout($page, $x, $y);
    $y += $this->_padding;

    // add (optional) intro box
    if($this->_intro_box) {
      $this->_intro_box->layout($page,$x,$y);
      $y += $this->_intro_box->getHeight();
      $x += $this->_intro_box->incrementIndent();
    }

    $this->_input->layout($page,$x,$y);
    $y += $this->_input->getHeight();

    // add (optional) qual box
    if($this->_qual_box) {
      $this->_qual_box->layout($page,$x+K_QUARTER_INCH,$y);
    }
  }

  public function render(): bool
  {
    return (
      $this->_input->render() &&
      ($this->_intro_box?->render() ?? true) &&
      ($this->_qual_box?->render() ?? true)
    );
  }
}
