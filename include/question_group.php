<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

/**
 * Class containing all of the content information for a specific question group
 *   - id:      group ID
 *   - name:    string used to identify the group in the survey editor 
 *   - content: ordered list of question IDs included in the group
 */
class QuestionGroup
{
  private int    $id;
  private string $name;
  private array  $content;

  /**
   * Constructs a new QuestionGroup instance
   * @param array{id: int, name: string, content?:list<int> } $data 
   */
  public function __construct(array $data)
  {
    $this->id      = $data['group_id'];
    $this->name    = $data['name'];
    $this->content = $data['content'] ?? [];
  }

  public function id() : int { return $this->id; }
  public function name() : string { return $this->name; }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function for_js() : array
  {
    return [
      'group_id' => $this->id,
      'name'     => $this->name,
      'content'  => $this->content,
    ];
  }

  /**
   * Returns the ordered list of question_ids associated with this group
   * @return list<int>
   */
  public function question_ids() : array { return $this->content; }
}

