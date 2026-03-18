<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('include/users.php'));
require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary/table_response_box.php'));

class SummaryQualifiersBox extends PDFBox
{
  private PDFTextBox $label;
  private SummaryTableResponseBox $responses;
  /** @var float[4] $linebox [x1,y1,x2,y2]*/
  private array $linebox;

  private const vspace=0;
  private const vgap=1; // mm
  private const linewidth=0.2; // mm

  /**
   * constructor
   * @param SummaryPDF $summaryPDF,
   * @param float $width 
   * @param string $label 
   * @param array $responses 
   * @return void 
   */
  public function __construct(SummaryPDF $summaryPDF,float $width,string $label,array $responses)
  {
    parent::__construct($summaryPDF);
    
    $this->height = 2*self::vgap + 2*self::linewidth;
    
    $this->label = new PDFTextBox(
      $summaryPDF,$width,$label,style:'I',size:K_SUMMARY_FONT_SMALL
    );
    $this->height += $this->label->getHeight();

    $this->responses = new SummaryTableResponseBox(
      $summaryPDF,$width,$responses,K_SUMMARY_FONT_SMALL,self::vspace,compact:true
    );
    $this->height += $this->responses->getHeight();
  }

  /**
   * Lays out the qualifier box and its children
   * @param float $x 
   * @param float $y 
   * @return bool 
   */
  protected function position(float $x, float $y): bool
  {
    parent::position($x, $y);

    $this->linebox[0] = $x;
    $this->linebox[2] = $x + $this->responses->getWidth();

    $this->label->position($x,$y);
    $y += $this->label->getHeight();

    $this->linebox[1] = $y;
    $y += self::vgap;

    $this->responses->position($x,$y);
    $y += $this->responses->getHeight() + self::vgap;

    $this->linebox[3] = $y;

    return true;
  }

  /**
   * Renders the qualifier box and its children
   * @return bool 
   */
  protected function render() : bool
  {
    if(!parent::render()) { return false; }
    if(!$this->label->render()) { return false; }
    if(!$this->responses->render()) { return false; }

    $this->ttpdf->setLineWidth(self::linewidth);
    $this->ttpdf->Line(
      $this->linebox[0],$this->linebox[1],
      $this->linebox[2],$this->linebox[1]
    );
    $this->ttpdf->Line(
      $this->linebox[0],$this->linebox[3],
      $this->linebox[2],$this->linebox[3]
    );
    return true;
  }
}