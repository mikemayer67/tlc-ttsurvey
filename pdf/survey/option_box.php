<?php
namespace tlc\tts;

use TCPDF;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/alignable_box.php'));
require_once(app_file('pdf/survey/enums.php'));

class SurveyOptionBox extends SurveyAlignableBox
{
  private PDFTextBox $_label;
  private SurveyJustification $_layout;

  private array $_bounds = [
    0,0, // x,y (set in laydown method)
    K_EIGHTH_INCH,K_EIGHTH_INCH, // width,height
    0, // corner radius (set in constructor)
  ];

  private float $_gap = 1;

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $max_width 
   * @param string $label 
   * @param OptionShape $shape 
   * @param SurveyJustification $layout 
   * @return void 
   */
  public function __construct(
    SurveyPDF $tcpdf, 
    float $max_width,
    string $label,
    OptionShape $shape, 
    SurveyJustification $layout)
  {
    parent::__construct($tcpdf);
    $this->_layout = $layout;

    $max_width -= $this->_bounds[2] + $this->_gap;
    $this->_label = new PDFTextBox($tcpdf,$max_width,$label);

    $size = min($this->_bounds[2], $this->_bounds[3]);
    switch($shape) {
      case OptionShape::RADIO:    $this->_bounds[4] = $size/2; break;
      case OptionShape::CHECKBOX: $this->_bounds[4] = $size/4; break;
    }

    $this->_width  = $this->_bounds[2] + $this->_gap + $this->_label->getWidth();
    $this->_height = max($this->_bounds[3], $this->_label->getHeight());
    $this->_aligned_width = $this->_width;
  }

  protected function layout(int $page, float $x, float $y)
  {
    parent::layout($page, $x, $y);

    if($this->_layout === SurveyJustification::LEFT) {
      $xc = $x;
      $xw = $x + $this->_bounds[2] + $this->_gap;
    } else {
      $xc = $x + $this->_aligned_width - $this->_bounds[2];
      $xw = $xc - $this->_gap - $this->_label->getWidth();
    }

    $dy = ($this->_bounds[3] - $this->_label->getHeight()) / 2;
    $yw = ($dy>0) ? $y + $dy : $y;
    $yc = ($dy<0) ? $y - $dy : $y;

    $this->_label->layout($page, $xw, $yw);
    $this->_bounds[0] = $xc;
    $this->_bounds[1] = $yc;
  }

  public function render(): bool
  {
    if(!$this->_label->render()) { return false; }
    
    $this->_tcpdf->setLineWidth(0.2);
    $this->_tcpdf->RoundedRect(...$this->_bounds);
    return true;
  }

}

