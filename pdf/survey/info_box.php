<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/config.php'));
require_once(app_file('survey/markdown.php'));

class SurveyInfoBox extends PDFBox
{
  private PDFBox $_box;
  private bool $_new_group = false;
  /**
   * @param SurveyPDF $surveyPDF 
   * @param float $max_width
   * @param array $question 
   * @return void 
   */
  public function __construct(SurveyPDF $surveyPDF, float $max_width, array $question)
  {
    parent::__construct($surveyPDF);

    $info = $question['info'];

    $this->_new_group = strtoupper($question['grouped']??"") === "NEW";

    $this->_width = $max_width;
    $this->_height = 0;

    if(possibleMarkdown($info)) {
      $this->_box = new PDFMarkdownBox($surveyPDF,$max_width,$info,size:K_SURVEY_FONT_MEDIUM);
    } else {
      $this->_box = new PDFTextBox($surveyPDF,$max_width,$info,size:K_SURVEY_FONT_MEDIUM,multi:true);
    }
    $this->_height += $this->_box->getHeight();
  }

  /**
   * Info boxes increate the indent for subsequent boxes if they are
   * starting a new question group
   * @return float 
   */
  public function incrementIndent() : float
  {
    return $this->_new_group ? K_QUARTER_INCH : 0;
  }

  /**
   * Manages the layout of a info box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position( float $x, float $y)
  {
    parent::position($x, $y);
    $this->_box->position($x, $y);
  }

  /**
   * Renders the content of a SurveyInfo box
   * @return bool 
   */
  protected function render(): bool
  {
    if (!parent::render()) { return false; }
    return $this->_box->render();
  }
}