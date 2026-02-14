<?php
namespace tlc\tts;

use TCPDF;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/option_box.php'));

class SurveyOptionsBox extends PDFBox
{
  private array   $_children;  // boxes
  private string  $_layout;
  private bool    $_multi_select;
  private ?SurveyOtherBox $_other;

  /**
   * @param TCPDF $tcpdf 
   * @param float $max_width 
   * @param mixed $question 
   * @param mixed $options array of all option strings
   * @return void 
   */
  public function __construct(TCPDF $tcpdf, float $max_width, $question, $options)
  {
  }

  protected function layout(int $page, float $x, float $y)
  {

  }

  public function render(): bool
  {
    return true;
  }





}