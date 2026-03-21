<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/section_header.php'));
require_once(app_file('pdf/survey/section_feedback.php'));
require_once(app_file('pdf/survey/group_box.php'));

/**
 * Responsible for parsing the survey content into top PDFBoxes
 * - Section boxes add a new section (which starts a new page)
 * - Group boxes add a box one or more questions
 * - Question boxes add a single question
 * - Section feedback boxes add the optional feedback entry
 */
class SurveyRootBox extends PDFRootBox
{
  /**
   * Constructs all of the top level child boxes for the survey form
   * @param SurveyPDF $surveyPDF 
   * @param array $content survey content structure data
   * @return void 
   */
  public function __construct(SurveyPDF $surveyPDF, array $content)
  {
    parent::__construct($surveyPDF);

    $max_width = $this->content_width();

    // Sort the sections by sequence
    $sections = $content['sections'];
    usort($sections, fn($a, $b) => $a['sequence'] <=> $b['sequence']);
    foreach ($sections as $section) {
      $this->add_section($max_width, $section, $content);
    }
  }

  /**
   * Adds section content boxes to the survey form
   * @param float $width 
   * @param array $section section specific content data
   * @param array $content overall survey content structure data
   * @return void 
   */
  private function add_section(float $width, array $section, array $content)
  {
    $box = new SurveySectionHeader($this->ttpdf, $width, $section);
    $this->addChild($box);

    $width -= $box->incrementIndent();

    $this->add_questions($width, $section['section_id'], $content);

    $feedback_prompt = $section['feedback'] ?? null;
    if($feedback_prompt) {
      $box = new SurveySectionFeedback($this->ttpdf, $width, $feedback_prompt);
      $this->addChild($box);
    }
  }

  /**
   * Adds all question content for the specified section id
   * @param float $width
   * @param int $sid section ID
   * @param array $content overall survey content structure data
   * @return void 
   */
  private function add_questions(float $width, int $sid, array $content): void
  {
    // find the questions to add to this section
    $questions = array_values(array_filter(
      $content['questions'],
      fn($q) => (
        (($q['section'] ?? null) === $sid) &&        // question must be associated with this section
        array_key_exists('sequence', $q)  // question must have a sequence index
      )
    ));

    // and sort them by sequence index
    usort($questions, function ($a, $b) {
      return $a['sequence'] <=> $b['sequence'];
    });

    // group the questions into groups that should not break between pages.
    $groups = [];
    $group = [];
    foreach($questions as $question) {
      switch( $question['grouped'] ?? 'NO' ) {
        case 'YES':
          $group[] = $question;
        break;
        case 'NEW':
          if ($group) { $groups[] = $group; }
          $group = [$question];
          break;
        default:
          if ($group) { $groups[] = $group; }
          $groups[] = [$question];
          $group = [];
          break;
      }
    }
    if($group) { $groups[] = $group; }

    // add question boxes to the survey
    foreach($groups as $questions) {
      $box = new SurveyGroupBox($this->ttpdf, $width, $questions, $content);
      $this->addChild($box);
    }
  }
}