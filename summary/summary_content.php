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

    foreach($content->sections() as $survey_section) 
    {
      $section_id = $survey_section->id();

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
        $this->sections[$section_id] = [
          'id'   => $section_id,
          'name' => $survey_section->name(),
          'collapsible' => $survey_section->collapsible(),
          'intro' => $survey_section->intro(),
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

  /**
   * Returns the section name for the specified section ID
   * @param int $section_id 
   * @return string 
   */
  public function section_name(int $section_id) : string
  {
    return $this->sections[$section_id]['name'] ?? "???";
  }

  /**
   * Returns whether or not the specified section is collapsible in the survey view
   * @param int $section_id 
   * @return bool 
   */
  public function section_is_collapsible(int $section_id) : bool
  { 
    return $this->sections[$section_id]['collapsible'] ?? true;
  }

  /**
   * Returns all of the groups of questions for the specified section ID
   * @param int $section_id 
   * @return list< list<Question> >
   */
  public function question_groups(int $section_id) : array
  {
    return $this->sections[$section_id]['groups'] ?? [];
  }

  /**
   * Returns all of the responses for all users for the specified question ID
   *   The responses are indexed by userid
   * 
   * @param int $question_id 
   * @return array<string, array{
   *    question_id : int,
   *    userid      : string,
   *    selected?   : int,
   *    freetext?   : string|null,
   *    qualifier?  : string|null,
   *    other?      : string|null,
   * }>
   */
  public function responses(int $question_id) : array
  {
    return $this->responses[$question_id] ?? [];
  }

  /**
   * Returns all of the option strings indexed by option_id
   * @return array{int, string}
   */
  public function all_options() : array { return $this->options; }

  /**
   * Return the text associated with the specified option ID
   * @param int $option_id 
   * @return string 
   */
  public function option(int $option_id) : string
  {
    return $this->options[$option_id] ?? "???";
  }
  
}