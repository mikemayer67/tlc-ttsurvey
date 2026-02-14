<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('survey/markdown.php'));

class SurveyIntroBox extends PDFBox
{
  private PDFBox $_box;

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $max_width 
   * @param string $intro 
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $max_width, string $intro)
  {
    parent::__construct($tcpdf);

    if (possibleMarkdown($intro)) {
      $this->_box = new PDFMarkdownBox($tcpdf, $max_width, $intro);
    } else {
      $this->_box = new PDFTextBox($tcpdf, $max_width, $intro, multi: true);
    }
    $this->_height = $this->_box->getHeight();
  }

  public function incrementIndent(): float { return K_QUARTER_INCH; }

  /**
   * Manages layout of a intro box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function layout(int $page, float $x, float $y)
  {
    parent::layout($page, $x, $y);
    $this->_box->layout($page, $x,$y);
  }

  /**
   * @return bool 
   */
  public function render(): bool
  {
    return $this->_box->render();
  }

}