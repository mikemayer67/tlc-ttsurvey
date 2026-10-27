<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary_pdf.php'));
require_once(app_file('pdf/summary/section_header.php'));
require_once(app_file('pdf/summary/info_box.php'));
require_once(app_file('pdf/summary/bool_box.php'));
require_once(app_file('pdf/summary/freetext_box.php'));
require_once(app_file('pdf/summary/select_box.php'));
require_once(app_file('include/question/types.php'));

/**
 * Responsible for parsing the survey responses into PDFBoxes
 */
class SummaryRootBox extends PDFRootBox
{
  private SummaryContent $content;
  /**
   * Constructs all top level child boxes for the summary
   * @param SummaryPDF $summaryPDF 
   * @param SummaryContent $content 
   */
  public function __construct(SummaryPDF $summaryPDF, SummaryContent $content)
  {
    parent::__construct($summaryPDF, top:3*K_QUARTER_INCH, right:K_QUARTER_INCH, left:K_HALF_INCH);
    $this->content = $content;

    foreach($content->section_ids() as $section_id)
    {
      $this->add_section($this->content_width(),$section_id);
    }
  }

  /**
   * Adds section content boxes to the summary
   * @param float $width 
   * @param int $section_id 
   */
  private function add_section(float $width, int $section_id) : void
  {
    $section_name = $this->content->section_name($section_id);
    $collapsible  = $this->content->section_is_collapsible($section_id);
    $box = new SummarySectionHeader($this->ttpdf, $width, $section_name, $collapsible);
    $this->addChild($box);

    $groups = $this->content->question_groups($section_id);
    foreach( $groups as $questions) {
      $this->add_questions($width, $questions);
    }
  }

  /**
   * Adds question boxes for the current section to the summary
   * @param float $max_width 
   * @param array $questions
   */
  private function add_questions(float $max_width, array $questions) : void
  {
    $prev    = null;
    $width   = $max_width;
    foreach($questions as $question) 
    {
      $box = null;

      $question_id = $question->id();
      $responses = $this->content->responses($question_id);

      switch($question->type()) {
        case QuestionType::Info:
          $box = new SummaryInfoBox($this->ttpdf, $width, $question, $prev);
          $width = $max_width - SummaryInfoBox::indent;
          break;
        case QuestionType::Bool:
          $box = new SummaryBoolBox($this->ttpdf,$width,$question,$responses,$prev);
          break;
        case QuestionType::FreeText:
          $box = new SummaryFreeTextBox($this->ttpdf,$width,$question,$responses,$prev);
          break;
        case QuestionType::SelectOne: // intentional fallthrough
        case QuestionType::SelectMulti:
          $options = $this->content->all_options();
          $box = new SummarySelectBox($this->ttpdf,$width,$question,$options,$responses,$prev);
          break;
      }

      // box should never not be set... but just in case.
      if($box)
      {
        $this->addChild($box);
        $prev = $box;
      }
    }
  }

  protected function render_child(PDFBox $child)
  {
    $section = $child->currentSection();
    if($section) {
      assert($this->ttpdf instanceof SummaryPDF);
      $this->ttpdf->setSection($section);
    }
    parent::render_child($child);
  }
}
