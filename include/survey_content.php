<?php
namespace tlc\tts;

use OutOfBoundsException;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/question.php'));
require_once(app_file('include/question_types.php'));
require_once(app_file('include/question_flags.php'));
require_once(app_file('include/survey_section.php'));
require_once(app_file('include/question_group.php'));

/**
 * Class containing all of the content information for the specified survey
 * 
 * Survey content must be accessed using the methods provided by
 *   this class. The content includes the following data structures:
 * 
 *   Options : indexed array of options shared by all questions in the survey
 *     option_id : unique identifier for each option (*array index)
 *     text : how option appears in the survey form
 *
 *   Sections:  ordered list of tlc\tts\SurveySection instances
 *   Groups:    indexed array of tlc\tts\QuestionGroup instances
 *   Questions: indexed array of tlc\tts\Question instances
 *
 *   Next IDs: associative array of next available IDs (across all surveys)
 *     survey:   next available survey ID
 *     question: next available question ID
 *     option:   next available option ID
 */
class SurveyContent
{
  private array $_options = [];
  private array $_sections = [];
  private array $_groups = [];
  private array $_questions = [];
  private array $_next_ids = [];

  /**
   * Cosntructor is private to force use of factory methods
   * @return void 
   */
  private function __construct() {}

  /**
   * Constructs and returns a SurveyContent instance populated from database
   * 
   * @param int $survey_id ID of the survey to load
   * @return SurveyContent 
   * @throws \OutOfBoundsException if susrvey_id is not found in the database
   */
  public static function fromSurveyID(int $survey_id) : SurveyContent
  {
    $content = new SurveyContent();
    $content->load_from_database($survey_id);
    return $content;
  }

  /**
   * Constructs and returns a SurveyContent instance from a JSON string
   * 
   * @param string $json JSON representation of the survey content
   *    (see parse_json_string for more info)
   * @return SurveyContent 
   */
  public static function fromJsonString(string $json) : SurveyContent
  {
    $content = new SurveyContent();
    $content->parse_json_string($json);
    return $content;
  }

  /**
   * Returns array of option strings common to all questions in the specified survey
   *
   * @return array<string>
   */
  public function options() : array { 
    return $this->_options; 
  }

  /**
   * Returns the option string associated with the specified option ID
   * @param int $id 
   * @return null|string 
   */
  public function option_text(int $id) : ?string {
    return $this->_options[$id] ?? null;
  }

  /**
   * Returns array of all sections in the specified survey
   * 
   * @return list<SurveySection>
   */
  public function sections() : array { return $this->_sections; }

  /**
   * Returns the survey section with the specified section ID
   * 
   * @param int $section_id 
   * @return null|SurveySection
   */
  public function section($section_id) : ?SurveySection { 
    return $this->_sections[$section_id] ?? null; 
  }

  /**
   * Returns array of all question groups in the specified survey
   *
   * @return list<QuestionGroup>
   */
  public function groups() : array { return $this->_groups; }

  /**
   * Returns the question group with the specified group ID
   * @param int $group_id 
   * @return null|QuestionGroup
   */
  public function group(int $group_id) : ?QuestionGroup {
    return $this->_groups[$group_id] ?? null;
  }

  /**
   * Returns array of all questions in the specified survey (and its ancestors)
   * @return array list<Question>
   */
  public function questions() : array { return $this->_questions; }

  /**
   * Returns the question with the specified group ID
   * @param int $question_id
   * @return null|Question
   */
  public function question(int $question_id) : ?Question
  { 
    return $this->_questions[$question_id] ?? null;
  }

  /**
   * Returns an array of the next ID to be used when creating a new survey, question, or option.
   *   survey:   next available survey ID (must be unique across all surveys)
   *   question: next available question ID (must be unique across all surveys)
   *   option:   next available option ID (must be unique within a given survey)
   * @return array<int> 
   */
  public function next_ids() : array { return $this->_next_ids; }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function for_js() : array
  {
    return [
      'options'   => $this->_options,
      'sections'  => array_map(
        fn($section) => $section->for_js(),
        $this->_sections
      ),
      'groups'    => array_map(
        fn($group) => $group->for_js(),
        $this->_groups
      ),
      'questions' => array_map(
        fn($question) => $question->for_js(),
        $this->_questions
      ),
      'next_ids'  => $this->_next_ids,
    ];
  }

  /**
   * Populates the SurveyContent data from database for the specified survey ID
   * @param int $survey_id 
   * @return void 
   * @throws OutOfBoundsException if survey_id is not found in the database
   */
  private function load_from_database(int $survey_id)
  {
    if(! $this->verify_id($survey_id)) {
      throw new \OutOfBoundsException("No survey content found for survey_id $survey_id");
    }
    $this->add_options($survey_id);
    $this->add_sections($survey_id);
    $this->add_groups($survey_id);
    $this->add_questions($survey_id);
    $this->add_next_ids($survey_id);
  }

  private function verify_id(int $survey_id)
  {
    $query = <<<SQL
      SELECT *
        FROM tlc_srv_surveys
       WHERE survey_id=(?);
    SQL;
    $rows = MySQLFetchAllAssoc($query, 'i', $survey_id);
    return count($rows) === 1;
  }

  private function add_options(int $survey_id)
  {
    $query = <<<SQL
      SELECT option_id, option_str as text
        FROM tlc_srv_survey_options
       WHERE survey_id=(?)
       ORDER BY option_id;
    SQL;
    $rows = MySQLFetchAllAssoc($query, 'i', $survey_id);

    if($rows) {
      $this->_options = array_column($rows, 'text', 'option_id');
    }
  }

  private function add_sections(int $survey_id)
  {
    // As the database keeps separate tables for identifying and
    //  populating survey sections, we need two queries.

    $query = <<<SQL
      SELECT section_id, name, collapsible, intro
      FROM   tlc_srv_sections
      WHERE survey_id=(?)
      ORDER BY section_id;
    SQL;
    $rows = MySQLFetchAllAssoc($query, 'i', $survey_id);
    if(!$rows) { return; }

    $sections = array_column($rows,null,'section_id');

    $query = <<<SQL
      SELECT section_id, group_id, question_id
        FROM tlc_srv_section_content 
       WHERE survey_id=(?)
       ORDER BY section_id, sequence
    SQL;
    $rows = MySQLFetchAllIndexed($query,'i',$survey_id);
    foreach($rows as [$section_id,$group_id,$question_id]) {
      $is_group = isset($group_id);
      $is_question = isset($question_id);
      if($is_group && $is_question) {
        internal_error("Section $section_id has content with both group and question IDs");
      }
      if($is_group || $is_question) {
        $sections[$section_id]['content'][] = [
          'type' => $is_group ? 'group' : 'question',
          'id'   => $is_group ? $group_id : $question_id,
        ];
      }
    }

    $this->_sections = array_map(
      fn($section) => new SurveySection($section),
      $sections
    );
  }

  private function add_groups(int $survey_id)
  {
    // As the database keeps separate tables for identifying and
    //  populating question groups, we need two queries.

    // Get all group IDs associated with the specified survey
    $query = <<<SQL
      SELECT group_id, name
        FROM tlc_srv_question_groups
       WHERE survey_id=(?)
    SQL;
    $rows = MySQLFetchAllAssoc($query, 'i', $survey_id);
    if (!$rows) { return; }

    $groups = array_column($rows, null, 'group_id');
    
    // Get all question IDs associated with each group in the survey
    //   The database enforces with foreign keys that the group IDs
    //   returned below are a subset of the group IDs found above
    $query = <<<SQL
      SELECT group_id, question_id
        FROM tlc_srv_group_content 
       WHERE survey_id=(?)
       ORDER BY group_id, sequence
    SQL;
    $rows = MySQLFetchAllIndexed($query,'i',$survey_id);
    foreach($rows as [$group_id,$question_id]) {
      $groups[$group_id]['content'][] = $question_id;
    }

    $this->_groups = array_map(
      fn($group) => new QuestionGroup($group),
      $groups
    );
    
  }

  private function add_questions(int $survey_id) 
  {
    $exclude = array_keys($this->_questions);
    $query = <<<SQL
      SELECT question_id, wording, question_type, question_flags as flags,
            other, qualifier, intro, info
        FROM tlc_srv_questions
      WHERE survey_id=(?)
    SQL;
    if($exclude) {
      $query .= " AND question_id not in (" . implode(',',$exclude) . ")";
    }
    $rows = MySQLFetchAllAssoc($query, 'i', $survey_id);

    if(!$rows) { return; }

    foreach($rows as $row) {
      $question = Question::from($row,$survey_id);
      $this->_questions[$question->id()] = $question;
    }

    // add questions found in ancestor surveys that are not in the current survey
    
    $query = <<<SQL
      SELECT parent_id from tlc_srv_surveys where survey_id=?;
    SQL;
    $parent_id = MySQLFetchValue($query,'i',$survey_id);
    if($parent_id !== null) 
    {
      $this->add_questions($parent_id);
    }
  }

  private function add_next_ids(int $survey_id)
  {
    $this->_next_ids = [
      'survey'   => 1 + MySQLFetchValue('select max(survey_id)   from tlc_srv_surveys'),
      'question' => 1 + MySQLFetchValue('select max(question_id) from tlc_srv_questions'),
      'option'   => 1 + MySQLFetchValue('select max(option_id)   from tlc_srv_survey_options where survey_id=(?)','i',$survey_id),
    ];
  }

  private function parse_json_string(string $json)
  {
    $content = json_decode($json, true);

    $this->_options   = $content['options'];
    $this->_next_ids  = $content['next_ids'];

    $this->_sections  = [];
    foreach($content['sections'] as $section_id => $section) {
      $this->_sections[$section_id] = new SurveySection($section);
    }

    $this->_groups = [];
    foreach($content['groups'] as $group_id => $group) {
      $this->_groups[$group_id] = new QuestionGroup($group);
    }

    $this->_questions = [];
    foreach($content['questions'] as $question_id => $question) {
      $this->_questions[$question_id] = Question::from($question);
    }
  }
}