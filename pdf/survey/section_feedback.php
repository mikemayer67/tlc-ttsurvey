<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));

class SurveySectionFeedback extends PDFBox
{
  private PDFTextBox $_wording_box;

  private array $_entry_box = [0,0,0,K_INCH];

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $width 
   * @param string $prompt 
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $width, string $prompt)
  {
    parent::__construct($tcpdf);

    $this->_wording_box = new PDFTextBox($tcpdf,$width,$prompt,size:K_SURVEY_FONT_MEDIUM);
    
    $this->_width = $width;
    $this->_height = $this->_wording_box->getHeight();
    
    $this->_entry_box[2] = $width;
    $this->_height += $this->_entry_box[3];
  }
  
  /**
   * Section boxes always reset the indent to 0
   * @return bool 
   */
  public function resetIndent(): bool
  {
    return true;
  }

  /**
   * Manages the layout of the section feedback box and its children
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position(float $x,float $y)
  {
    parent::position($x,$y);

    $this->_wording_box->position($x, $y);
    $y += $this->_wording_box->getHeight();

    $this->_entry_box[0] = $x;
    $this->_entry_box[1] = $y;
  }

  /**
   * Renders the content of a SurveyFeedback box
   * @return bool 
   */
  protected function render() : bool
  {    
    $box = $this->_wording_box;
    if(!$box->render()) { return false; }
    
    // not currently drawing the entry box, but if desired, uncomment the following
    //$this->_tcpdf->setLineWidth(0.2);
    //$this->_tcpdf->Rect(...$this->_entry_box);

    return true;
  }
}