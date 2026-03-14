<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary_pdf.php'));
require_once(app_file('pdf/summary/section_header.php'));
require_once(app_file('pdf/summary/section_feedback.php'));
require_once(app_file('summary/sections.php'));

/**
 * Responsible for parsing the survey responses into PDFBoxes
 */
class SummaryRootBox extends PDFRootBox
{
  /**
   * Constructs all top level child boxes for the summary
   * @param SummaryPDF $summaryPDF 
   * @param array $content 
   * @param array $responses 
   * @return void 
   */
  public function __construct(SummaryPDF $summaryPDF, array $content, array $responses)
  {
    parent::__construct($summaryPDF, top:3*K_QUARTER_INCH, right:K_QUARTER_INCH, left:K_QUARTER_INCH);
    $sections = summary_sections($content);
    foreach($sections as $section) {
      $this->add_section($this->content_width(),$section,$content,$responses);
    }

    return;
  }

  /**
   * Adds section content boxes to the summary
   * @param float $width 
   * @param array $section section specific content data
   * @param array $content overall survey content
   * @param array $responses overall response data
   * @return void 
   */
  private function add_section(float $width, array $section, array $content, array $responses)
  {
    $box = new SummarySectionHeader($this->ttpdf,$width,$section);
    $this->addChild($box);

    // @@@ Add questions here

    $feedback = $section['feedback'] ?? null;
    if($feedback) {
      $box = new SummarySectionFeedback($this->ttpdf, $width, $section, $responses);
      $this->addChild($box);
    }
  }

  protected function render_child(PDFBox $child): bool
  {
    $section = $child->currentSection();
    if($section) {
      assert($this->ttpdf instanceof SummaryPDF);
      $this->ttpdf->setSection($section);
    }
    return parent::render_child($child);
  }
}
