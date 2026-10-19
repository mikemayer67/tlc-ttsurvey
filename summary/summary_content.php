<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/survey_content.php'));

/**
 * Class containing all the survey and response data necessary to generate a summary of responses
 */
class SummaryContent
{
  private array $sections;
  private array $options;
  private array $responses;

  public function __construct(SurveyContent $content, array $responses)
  {
    $this->responses = $responses;
    $this->options   = $content->options();

    $this->sections = [];

    foreach($content->sections() as $survey_section) {
      $groups = [];
      foreach($survey_section->content() as ['type'=>$type, 'id'=>$id]) {
        if($type === 'question') {
          $question = $content->question($id);
          // only include info blocks that are inside real groups
          if(! $question instanceof InfoQuestion ) {
            $groups[] = [ $question ];
          }
        } else {
          $group = $content->group($id);
          $groups[] = array_map(
            fn($question_id) => $content->question($question_id),
            $group->question_ids()
          );
        }
      }
      if($groups) {
        $this->sections[] = [
          'id'   => $survey_section->id(),
          'name' => $survey_section->name(),
          'groups' => $groups,
        ];
      }
    }
  }

  /**
   * Returns the ordered list of sections IDs to include in the summary
   * @return list<int>
   */
  public function section_ids() : array
  {
    return array_map( fn($section) => $section['id'], $this->sections );
  }
}