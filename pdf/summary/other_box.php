<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('include/users.php'));
require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary/table_response_box.php'));

class SummaryOtherBox extends PDFBox
{
  private PDFTextBox $label;
  private SummaryTableResponseBox $responses;

  private const vspace=0;
  private const vgap=1; // mm
  private const indent = K_QUARTER_INCH;

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
    
    $this->label = new PDFTextBox(
      $summaryPDF,$width,$label,style:'B',size:K_SUMMARY_FONT_MEDIUM
    );
    $this->width = $width;
    $this->height = self::vgap + $this->label->getHeight();

    $width -= self::indent;
    $this->responses = new SummaryTableResponseBox(
      $summaryPDF,$width,$responses,K_SUMMARY_FONT_MEDIUM,self::vspace,compact:true
    );
    $this->height += $this->responses->getHeight();
  }

  /**
   * Lays out the qualifier box and its children
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position(float $x, float $y)
  {
    parent::position($x, $y);
    $this->label->position($x,$y);
    $x += self::indent;
    $y += $this->label->getHeight() + self::vgap;
    $this->responses->position($x,$y);
  }

  /**
   * Renders the qualifier box and its children
   * @return void
   */
  protected function render()
  {
    parent::render();
    $this->label->render();
    $this->responses->render();
  }
}