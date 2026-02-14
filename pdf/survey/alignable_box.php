<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/enums.php'));

abstract class SurveyAlignableBox extends PDFBox 
{
  protected float $_aligned_width = 0;
  protected SurveyJustification $_justification = SurveyJustification::LEFT;

  /**
   * Getter for aligned width, i.e. the part of the box which must be aligned
   * @return float 
   */
  public function getAlignedWidth() : float {return $this->_aligned_width;}

  /**
   * Setter for aligned width, i.e. the part of the box which must be aligned
   * @return float 
   */
  public function setAlignedWidth(float $w) { $this->_aligned_width = $w; }

  /**
   * Getter for box justification
   * @return SurveyJustification (LEFT or RIGHT)
   */
  public function getJustification() : SurveyJustification
  {
    return $this->_justification;
  }

  /**
   * Setter for box justification (LEFT or RIGHT)
   * @param SurveyJustification $justification 
   * @return void 
   */
  public function setJustification(SurveyJustification $justification)
  {
    $this->_justification = $justification;
  }
}