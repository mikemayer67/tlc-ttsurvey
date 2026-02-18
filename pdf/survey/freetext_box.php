<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/intro_box.php'));

class SurveyFreetextBox extends PDFBox
{
  private ?SurveyIntroBox $_intro_box = null;
  private PDFBox          $_wording_box;

  private array $_entry_box = [0,0,0,3*K_QUARTER_INCH];
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

    $wording = $question['wording'];
    $intro   = $question['intro'] ?? null;

    $this->_width = $max_width;
    $this->_height = 0;

    if($intro) {
      $this->_intro_box = new SurveyIntroBox($tcpdf,$max_width,$intro);
      $max_width -= $this->_intro_box->incrementIndent();
      $this->_height += $this->_intro_box->getHeight();
    }

    $this->_wording_box = new PDFTextBox($tcpdf,$max_width,$wording);
    $this->_height += $this->_wording_box->getHeight();

    $this->_entry_box[2] = $max_width;
    $this->_height += $this->_entry_box[3];
  }

  /**
   * Manages positioning of a free text box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position( float $x, float $y)
  {
    parent::position($x, $y);

    if($this->_intro_box) {
      $this->_intro_box->position($x, $y);
      $y += $this->_intro_box->getHeight() + $this->_gap;
      $x += $this->_intro_box->incrementIndent();
    }

    $this->_wording_box->position($x, $y);
    $y += $this->_wording_box->getHeight();

    $this->_entry_box[0] = $x;
    $this->_entry_box[1] = $y;
  }

  /**
   * Renders the content of a free text box
   * @return bool 
   */
  protected function render() : bool
  {
    $box = $this->_intro_box;
    if($box) {
      if(!$box->render()) { return false; }
    }
    $box = $this->_wording_box;
    if(!$box->render()) { return false; }
    $this->_tcpdf->setLineWidth(0.2);
    $this->_tcpdf->Rect(...$this->_entry_box);

    return true;
  }
}
