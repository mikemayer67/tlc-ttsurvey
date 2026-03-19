<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/config.php'));

class SurveyQualifierBox extends PDFBox
{
  private PDFBox $label;
  private int    $label_width = 0;
  private int    $label_height = 0;
  private bool   $multi_line = false;

  private array $entry_box = [0,0,0,K_QUARTER_INCH];

  private const vpad = 1; // mm
  private const hpad = 1; // mm

  /**
   * @param SurveyPDF $surveyPDF 
   * @param float $max_width 
   * @param string $label 
   * @param int $fontsize (default = K_SURVEY_FONT_MEDIUM)
   * @return void 
   */
  public function __construct(SurveyPDF $surveyPDF,float $max_width,string $label,int $fontsize=K_SURVEY_FONT_MEDIUM)
  {
    parent::__construct($surveyPDF);

    $this->entry_box[2] = min(3*K_INCH, $max_width/2);

    $this->label = new PDFTextBox($surveyPDF,$max_width,$label,size:$fontsize);
    $this->label_width  = $this->label->getWidth();
    $this->label_height = $this->label->getHeight();
    if($this->label_width + $this->entry_box[2] + self::hpad < $max_width) {
      $this->multi_line = false;
      $this->height += max($this->label_height,$this->entry_box[3]);
    } else {
      $this->multi_line = true;
      $this->height += $this->label_height + self::vpad + $this->entry_box[3];
    }
    $this->width = min($max_width, $this->label_width + $this->entry_box[2] + self::hpad);
  }

  /**
   * Manages positioning of a qualifer box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position(float $x, float $y)
  {
    parent::position($x, $y);

    if($this->multi_line) {
      $this->label->position($x, $y);
      // set the (x,y) for the entry box on the next line
      $y += $this->label_height + self::vpad;
      $x += K_INCH;
    } else {
      $dy = ($this->entry_box[3] - $this->label_height)/2;
      if($dy >= 0) {
        // shift the label down so as to center on entry
        $this->label->position($x, $y+$dy);
      } else {
        $this->label->position($x, $y);
        // shift the entry down so as to center on label
        $y += $dy;
      }
      // shift the entry horizontally to after the label
      $x += $this->label_width + self::hpad;
    }
    $this->entry_box[0] = $x;
    $this->entry_box[1] = $y;
  }

  public function render()
  {
    parent::render();
    $this->label->render();

    $x1 = $this->entry_box[0];
    $y1 = $this->entry_box[1];
    $x2 = $x1 + $this->entry_box[2];
    $y2 = $y1 + $this->entry_box[3];
    $this->ttpdf->setLineWidth(0.2);
    $this->ttpdf->Line($x1,$y2,$x2,$y2);
    // $this->ttpdf->Rect(...$this->entry_box);
  }

  protected function debug_color(): array
  {
    return [255,0,255];
  }
}