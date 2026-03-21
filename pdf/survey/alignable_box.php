<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/enums.php'));

/**
 * SurveyAlignableBox extends PDFBox allowing for alignment and justification
 * This is an abstract class that simply provides accessors to the
 *   alignment and justification attributes.
 * It is up to subclasses to determine what to do with the 
 *   alignment and justification attributes.
 * @package tlc\tts
 */
abstract class SurveyAlignableBox extends PDFBox 
{
  protected float $aligned_width = 0;
  protected SurveyJustification $justification = SurveyJustification::LEFT;

  /**
   * @param SurveyPDF $surveyPDF 
   * @param null|SurveyJustification $justification 
   * @param null|float $width 
   * @return void 
   */
  public function __construct(
    SurveyPDF $surveyPDF,
    ?SurveyJustification $justification = null,
    ?float $width = null )
  {
    parent::__construct($surveyPDF);
    if($width !== null ) {
      $this->aligned_width = $width;
    }
    if($justification !== null) {
      $this->justification = $justification;
    }
  }

  /**
   * Getter for aligned width, i.e. the part of the box which must be aligned
   * @return float 
   */
  public function getAlignedWidth() : float {return $this->aligned_width;}

  /**
   * Setter for aligned width, i.e. the part of the box which must be aligned
   * @return float 
   */
  public function setAlignedWidth(float $w) { $this->aligned_width = $w; }

  /**
   * Getter for box justification
   * @return SurveyJustification (LEFT or RIGHT)
   */
  public function getJustification() : SurveyJustification
  {
    return $this->justification;
  }

  /**
   * Setter for box justification (LEFT or RIGHT)
   * @param SurveyJustification $justification 
   * @return void 
   */
  public function setJustification(SurveyJustification $justification)
  {
    $this->justification = $justification;
  }
}