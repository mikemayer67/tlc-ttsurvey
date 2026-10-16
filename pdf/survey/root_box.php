<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/section_header.php'));
require_once(app_file('pdf/survey/group_box.php'));

/**
 * Responsible for parsing the survey content into top PDFBoxes
 * - Section boxes add a new section (which starts a new page)
 * - Group boxes add a box one or more questions
 * - Question boxes add a single question
 */
class SurveyRootBox extends PDFRootBox
{
  /**
   * Constructs all of the top level child boxes for the survey form
   * @param SurveyPDF $surveyPDF  
   * @param SurveyContent $content
   * @return void 
   */
  public function __construct(SurveyPDF $surveyPDF, SurveyContent $content)
  {
    parent::__construct($surveyPDF);

    $max_width = $this->content_width();

    // Sort the sections by sequence
    foreach ($content->sections() as $section) 
    {
      // reset the width for the next section to full width
      $width = $max_width;

      // add the section header
      $box = new SurveySectionHeader($this->ttpdf, $width, $section);
      $this->addChild($box);
    
      // decrease the available width by the header indent (may be zero)
      $width -= $box->indentDelta();

      // add the section content
      foreach($section['content'] as ['type'=>$type, 'id'=>$id])
      {
        // determine if the item is group or question and extract question IDs
        $question_ids = [];
        if($type === 'question') {
          // aka... a "group of one"
          $question_ids = [$id];
        } elseif($type === 'group') {
          $group = $content->group($id);
          if(isset($group)) { $question_ids = $group['content']; }
        } else {
          internal_error("Unknown section item type '$type'");
        }

        // map question IDs to question data
        $questions = array_filter(
          array_map(
            fn($question_id) => $content->question($question_id),
            $question_ids
          ),
          fn($question) => isset($question)
        );

        // and now render those questions:
        $box = new SurveyGroupBox($this->ttpdf, $width, $questions, $content);
        $this->addChild($box);
      }
    }
  }
}