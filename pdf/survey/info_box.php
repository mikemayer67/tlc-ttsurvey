<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/config.php'));
require_once(app_file('survey/markdown.php'));

class SurveyInfoBox extends PDFBox
{
  private PDFBox $box;
  private bool $new_group = false;
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

    $this->new_group = strtoupper($question['grouped']??"") === "NEW";

    $this->width = $max_width;
    $this->height = 0;

    if(possibleMarkdown($info)) {
      $this->box = new PDFMarkdownBox($surveyPDF,$max_width,$info,size:K_SURVEY_FONT_MEDIUM);
    } else {
      $this->box = new PDFTextBox($surveyPDF,$max_width,$info,size:K_SURVEY_FONT_MEDIUM,multi:true);
    }
    $this->height += $this->box->getHeight();
  }

  /**
   * Info boxes increate the indent for subsequent boxes if they are
   * starting a new question group
   * @return float 
   */
  public function incrementIndent() : float
  {
    return $this->new_group ? K_QUARTER_INCH : 0;
  }

  /**
   * Manages the layout of a info box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return bool 
   */
  protected function position(float $x, float $y) : bool
  {
    parent::position($x, $y);
    $this->box->position($x, $y);
    return true;
  }

  /**
   * Renders the content of a SurveyInfo box
   * @return bool 
   */
  protected function render(): bool
  {
    if (!parent::render()) { return false; }
    return $this->box->render();
  }
}