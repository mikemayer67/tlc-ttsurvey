<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));

class SurveyQualifierBox extends PDFBox
{
  private PDFBox $_label;
  private int    $_label_width = 0;
  private int    $_label_height = 0;
  private bool   $_multi_line = false;

  private array $_entry_box = [0,0,0,K_QUARTER_INCH];
  private float $_gap = 1; // mm

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $max_width 
   * @param string $label 
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $max_width, string $label)
  {
    parent::__construct($tcpdf);

    $this->_entry_box[2] = min(3*K_INCH, $max_width/2);

    $this->_label = new PDFTextBox($tcpdf, $max_width, $label);
    $this->_label_width  = $this->_label->getWidth();
    $this->_label_height = $this->_label->getHeight();
    if($this->_label_width + $this->_entry_box[2] + $this->_gap < $max_width) {
      $this->_multi_line = false;
      $this->_height += max($this->_label_height,$this->_entry_box[3]);
    } else {
      $this->_multi_line = true;
      $this->_height += $this->_label_height + $this->_gap + $this->_entry_box[3];
    }
  }

  /**
   * Manages positioning of a qualifer box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position( float $x, float $y)
  {
    parent::position($x, $y);

    if($this->_multi_line) {
      $this->_label->position($x, $y);
      // set the (x,y) for the entry box on the next line
      $y += $this->_label_height + $this->_gap;
      $x += K_INCH;
    } else {
      $dy = ($this->_entry_box[3] - $this->_label_height)/2;
      if($dy >= 0) {
        // shift the label down so as to center on entry
        $this->_label->position($x, $y+$dy);
      } else {
        $this->_label->position($x, $y);
        // shift the entry down so as to center on label
        $y += $dy;
      }
      // shift the entry horizontally to after the label
      $x += $this->_label_width + $this->_gap;
    }
    $this->_entry_box[0] = $x;
    $this->_entry_box[1] = $y;
  }

  public function render(): bool
  {
    if(!$this->_label->render()) { return false; }
    $this->_tcpdf->setLineWidth(0.2);
    $this->_tcpdf->Rect(...$this->_entry_box);
    return true;
  }
}