<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary_pdf.php'));
require_once(app_file('pdf/summary/section_header.php'));
require_once(app_file('pdf/summary/section_feedback.php'));
require_once(app_file('pdf/summary/info_box.php'));
require_once(app_file('pdf/summary/bool_box.php'));
require_once(app_file('pdf/summary/freetext_box.php'));
require_once(app_file('pdf/summary/select_box.php'));
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

    $this->add_questions($width, $section, $content, $responses);

    $feedback = $section['feedback'] ?? null;
    if($feedback) {
      $box = new SummarySectionFeedback($this->ttpdf, $width, $section, $responses);
      $this->addChild($box);
    }
  }

  /**
   * Adds question boxes for the current section to the summary
   * @param float $max_width 
   * @param array $section section specific content data
   * @param array $content overall survey content
   * @param array $responses overall response data
   * @return void 
   */
  private function add_questions(float $max_width, array $section, array $content, array $responses)
  {
    $sid = $section['section_id'];

    $questions = $content['questions'];
    $questions = array_filter($questions, fn($a) => ($a['section']??null) === $sid );
    uasort($questions,fn($a,$b) => $a['sequence'] <=> $b['sequence']);

    $grouped = false;
    $prev    = null;
    $width   = $max_width;
    foreach($questions as $question) {
      switch(strtoupper($question['grouped']??'NO')) {
        case 'NO':
          $grouped = false;
          $prev    = null;
          $width   = $max_width;
          break;
        case 'NEW':
          $grouped = true;
          $prev    = null;
          break;
        case 'YES':
          $grouped = true;
          break;
      }

      $box = null;

      $type = strtolower($question['type']);
      switch($type) {
        case 'info':
          if($grouped) {
            $box = new SummaryInfoBox($this->ttpdf,$width,$question,$prev);
            // @@@ add into 
            $width = $max_width - SummaryInfoBox::indent;
          }
          break;
        case 'bool':
          $box = new SummaryBoolBox($this->ttpdf,$width,$question,$responses,$prev);
          break;
        case 'freetext':
          $box = new SummaryFreetextBox($this->ttpdf,$width,$question,$responses,$prev);
          break;
        case 'select_one': // intentional fallthrough
        case 'select_multi':
          $box = new SummarySelectBox($this->ttpdf,$width,$question,$responses,$prev);
          break;
      }

      if($box) {
        $this->addChild($box);
        $prev = $box;
      }
    }
  }

  /**
   * Adds an info text box to the summary
   *   only displays inside of question group
   *   questions before the info box should stand alone
   *   questons in the group after the info box should be indented
   * @param float $width
   * @param string $info 
   * @return void 
   */
  private function add_info(float $width, string $info)
  {

    $info_text = strip_markdown($info);
    // @@@ create PDFTextBox and add it to summary
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
