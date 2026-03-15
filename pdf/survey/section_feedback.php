<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));

class SurveySectionFeedback extends PDFBox
{
  private PDFTextBox $wording_box;

  private array $entry_box = [0,0,0,K_INCH];

  /**
   * @param SurveyPDF $surveyPDF 
   * @param float $width 
   * @param string $prompt 
   * @return void 
   */
  public function __construct(SurveyPDF $surveyPDF, float $width, string $prompt)
  {
    parent::__construct($surveyPDF);

    $this->wording_box = new PDFTextBox($surveyPDF,$width,$prompt,size:K_SURVEY_FONT_MEDIUM);
    
    $this->width = $width;
    $this->height = $this->wording_box->getHeight();
    
    $this->entry_box[2] = $width;
    $this->height += $this->entry_box[3];
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
   * @return bool 
   */
  protected function position(float $x,float $y) : bool
  {
    parent::position($x,$y);

    $this->wording_box->position($x, $y);
    $y += $this->wording_box->getHeight();

    $this->entry_box[0] = $x;
    $this->entry_box[1] = $y;
    return true;
  }

  /**
   * Renders the content of a SurveyFeedback box
   * @return bool 
   */
  protected function render() : bool
  {    
    if (!parent::render()) { return false; }
    $box = $this->wording_box;
    if(!$box->render()) { return false; }
    
    // not currently drawing the entry box, but if desired, uncomment the following
    //$this->ttpdf->setLineWidth(0.2);
    //$this->ttpdf->Rect(...$this->entry_box);

    return true;
  }
}