<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/alignment_box.php'));
require_once(app_file('pdf/survey/intro_box.php'));
require_once(app_file('pdf/survey/qualifier_box.php'));
require_once(app_file('pdf/survey/option_box.php'));
require_once(app_file('pdf/survey/enums.php'));

class SurveySelectBox extends SurveyAlignableBox
{
  private PDFBox  $_wording_box;
  private float   $_wording_height = 0;
  private float   $_wording_width  = 0;
  private SurveyOptionBox     $_option_box;
  private ?SurveyIntroBox     $_intro_box = null;
  private ?SurveyQualifierBox $_qual_box = null;

  private array $_checkbox = [
    0,0, // x,y
    K_EIGHTH_INCH,K_EIGHTH_INCH, // width,height
    K_INCH/32, // corner radius
    ];

  private float $_gap = 1; // mm

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
    $qual    = $question['qualifier'] ?? null;

    if($intro) {
      $this->_intro_box = new SurveyIntroBox($tcpdf,$max_width,$intro);
      $max_width -= $this->_intro_box->incrementIndent();
      $this->_height += $this->_intro_box->getHeight();
    }

    $box = new PDFTextBox($tcpdf,$max_width,$wording);
    $this->_wording_box = $box;
    $this->_wording_width = $box->getWidth();
    $this->_wording_height = $box->getHeight();
    $this->_height += max($this->_wording_height, $this->_checkbox[3]);

    $this->setAlignedWidth($box->getWidth() + $this->_checkbox[2] + $this->_gap);
    $this->justification($question['layout'] ?? 'LEFT');

    if($qual) {
      $this->_qual_box = new SurveyQualifierBox($tcpdf,$max_width,$qual);
      $this->_height += $this->_qual_box->getHeight();
    }
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

    // add (optional) intro box
    if($this->_intro_box) {
      $this->_intro_box->layout($page,$x,$y);
      $y += $this->_intro_box->getHeight();
      $x += $this->_intro_box->incrementIndent();
    }

    // add wording box + checkbox
    if($this->justification() === 'LEFT') {
      $xc = $x;
      $xw = $xc + $this->_checkbox[2] + $this->_gap;
    } else {
      $xc = $x + $this->getAlignedWidth() - $this->_checkbox[2];
      $xw = $xc - ( $this->_gap + $this->_wording_width );
    }
    $dy = ($this->_checkbox[3] - $this->_wording_height)/2;
    $yw = ($dy > 0) ? $y+$dy : $y;
    $yc = ($dy > 0) ? $y     : $y - $dy;

    $this->_wording_box->layout($page, $xw, $yw);
    $this->_checkbox[0] = $xc;
    $this->_checkbox[1] = $yc;

    $y += $this->_wording_box->getHeight();

    // add (optional) qual box
    if($this->_qual_box) {
      $this->_qual_box->layout($page,$x+K_QUARTER_INCH,$y);
    }
  }

  public function render(): bool
  {
    if(
      ($this->_intro_box?->render() ?? true) &&
      $this->_wording_box->render() &&
      ($this->_qual_box?->render() ?? true )
    ) {
      $this->_tcpdf->setLineWidth(0.2);
      $this->_tcpdf->RoundedRect(...$this->_checkbox);
      return true;
    }
    return false;
  }
}
