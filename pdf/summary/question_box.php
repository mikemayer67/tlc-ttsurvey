<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));

/**
 * SummaryQuestionBox extends PDFBox to handle linking of boxes which should 
 *   be kept together on the same page in the summary if possible.
 * @package tlc\tts
 */
class SummaryQuestionBox extends PDFBox
{
  protected ?SummaryQuestionBox $next;
  protected ?SummaryQuestionBox $prev;

  protected const indent = K_QUARTER_INCH;
  protected const vgap = 2;

  public function __construct(SummaryPDF $summaryPDF, ?SummaryQuestionBox $prev=null)
  {
    parent::__construct($summaryPDF);
    $this->prev = $prev;
    $this->next = null;
    if($prev) { $prev->next = $this; }

    $this->top_pad = K_EIGHTH_INCH;
    $this->bottom_pad = K_EIGHTH_INCH;
  }

  /**
   * resets the indentation if first item in a group
   * @return bool 
   */
  public function resetIndent() : bool 
  {
    return $this->prev === null;
  }
}