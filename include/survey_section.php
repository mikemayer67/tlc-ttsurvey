<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

/**
 * Class containing all of the content information for a specific survey section
 *   - id:
 *   - name:        name of the section displayed in the survey
 *   - collapsible: flag indicating if section can be opened/closed in the survey
 *   - intro:       optional text to display at top of the section
 *   - content:     ordered list of group and question IDs in the section
 */
class SurveySection
{
  private int     $id;
  private string  $name;
  private bool    $collapsible;
  private ?string $intro;
  private array   $content;

  /**
   * Constructs a new SurveySection instance
   * @param array{
   *    section_id:   int,
   *    name:         string,
   *    collapsible?: bool,
   *    intro?:       string,
   *    content?:     list<array{type: 'question'|'group', id: int}>
   *   } $data
   */
  public function __construct(array $data)
  {
    $this->id          = $data['section_id'];
    $this->name        = $data['name'];
    $this->collapsible = $data['collapsible'] ?? true;
    $this->intro       = $data['intro'] ?? null;
    $this->content     = $data['content'] ?? [];
  }

  public function id() : int { return $this->id; }
  public function name() : string { return $this->name; }
  public function collapsible() : bool { return $this->collapsible; }
  
  public function has_intro() : bool    { return $this->intro ? true : false; }
  public function intro()     : ?string { return $this->intro; }

  /**
   * Returns the section content as a list of question or group IDs
   * @return list<array{type: 'question'|'group', id: int}>
   */
  public function content() : array { return $this->content; }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function for_js() : array
  {
    return [
      'section_id'  => $this->id,
      'name'        => $this->name,
      'collapsible' => $this->collapsible ? 1 : 0,
      'intro'       => $this->intro ?? "",
      'content'     => $this->content,
    ];
  }

}