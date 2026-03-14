<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/info_box.php'));
require_once(app_file('pdf/survey/freetext_box.php'));
require_once(app_file('pdf/survey/bool_box.php'));
require_once(app_file('pdf/survey/select_box.php'));

class SurveyGroupBox extends PDFBox
{
  /**
   * @var PDFBox[] child question boxes
   */
  private array $child_boxes = [];

  /**
   * @param SurveyPDF $surveyPDF 
   * @param float $max_width
   * @param array $questions 
   * @return void 
   */
  public function __construct(SurveyPDF $surveyPDF, float $max_width, array $questions, array $content)
  {
    parent::__construct($surveyPDF);

    $this->width = 0;
    $this->height = 0;

    $this->top_pad    = 2; // mm
    $this->bottom_pad = 1; // mm

    $aligned_width = 0;
    foreach($questions as $question) {
      $type = $question['type'];

      switch($type) {
        case 'INFO':
          $box = new SurveyInfoBox($surveyPDF,$max_width,$question);
          break;
        case 'FREETEXT':
          $box = new SurveyFreetextBox($surveyPDF,$max_width,$question);
          break;
        case 'BOOL':
          $box = new SurveyBoolBox($surveyPDF,$max_width,$question);
          break;
        case 'SELECT_ONE':
        case 'SELECT_MULTI':
          $box = new SurveySelectBox($surveyPDF,$max_width,$question,$content['options']);
          break;
      }
      $this->height += $box->getHeight();
      $this->width = max($this->width, $box->getWidth());
      $this->child_boxes[] = $box;

      if($box instanceof SurveyAlignableBox) {
        $aligned_width = max($aligned_width, $box->getAlignedWidth());
      }

      $max_width -= $box->incrementIndent();
    }
    foreach($this->child_boxes as $box) {
      if($box instanceof SurveyAlignableBox) {
        $box->setAlignedWidth($aligned_width);
      }
    }
  }

  /**
   * Manages the layout of a group box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position( float $x, float $y)
  {
    parent::position($x, $y);

    foreach($this->child_boxes as $box) 
    {
      $box->position($x,$y);
      $y += $box->getHeight();
      $x += $box->incrementIndent();
    }
  }

  /**
   * Renders the content of a SurveyGroupBox
   * @return bool 
   */
  protected function render() : bool
  {
    if (!parent::render()) { return false; }
    foreach($this->child_boxes as $box) 
    {
      if(!$box->render()) { return false; }
    }

    return true;
  }
}