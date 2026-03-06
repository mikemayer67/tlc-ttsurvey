<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary_pdf.php'));

/**
 * Responsible for parsing the survey responses into PDFBoxes
 */
class SummaryRootBox extends PDFRootBox
{
  protected SummaryPDF $summaryPDF;
  /**
   * Constructs all top level child boxes for the summary
   * @param SummaryPDF $summaryPDF 
   * @param array $content 
   * @param array $responses 
   * @return void 
   */
  public function __construct(SummaryPDF $summaryPDF, array $content, array $responses)
  {
    $this->summaryPDF = $summaryPDF;
    parent::__construct($summaryPDF);
  }

  protected function render_child(PDFBox $child): bool
  {
    $section = $child->currentSection();
    if($section) {
      $this->summaryPDF->setSection($section);
    }
    return parent::render_child($child);
  }
}
