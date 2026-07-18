<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/question_types.php'));
require_once(app_file('include/question_flags.php'));

/**
 * Class containing all of the content information for the specified survey
 * This includes:
 *   options : selectable response options (shared by all questions in the survey)
 *     option_id : unique identifier for each option (*array index)
 *     text : how option appears in the survey form
 *   sections:
 *      section_id : unique identifier for each section (*array index) 
 *      sequence : order this section appears in the survey
 *      name : name of this section 
 *      collapsible : truthy/falsey value if the section can be opened/closed in the survey
 *      intro : optional text to display at top of the section
 *      content : array of questions/groups in this section (in order)
 *         type: 'question' or 'group'
 *         id: question ID or group ID
 *   groups:
 *     group_id : unique identifier for each group
 *     name : group name as it will appear in the survey editor
 *     content : array of question IDs that appear in the group (in order)
 *   questions:
 *     id : unique identifier for each question
 *     type: QuestionType
 *     wording: how question appears in the survey
 *     intro: optional introductory text shown before question
 *     qualifier: (BOOL and SELECT only) label for optional freetext field in response
 *     other_flag: (SELECT only) if an "other" field will be included in response
 *     other: (SELECT only) label for "other" field in response
 *     info: (INFO only) the body of the info message
 *     popup: (all but INFO) text in popup hint in the survey
 *     layout: QuestionLayout
 *     render_in_group: (INFO only)
 *     options: (SELECT only) the list of selectable options
 *   next_ids:
 *     survey:   next available survey ID (must be unique across all surveys)
 *     question: next available question ID (must be unique across all surveys)
 *     option:   next available option ID (must be unique within a given survey)
 * 
 * @param int $survey_id : the survey of interest
 */
class SurveyContent
{
  private ?int  $_survey_id = null;
  private array $_options = [];
  private array $_sections = [];
  private array $_groups = [];
  private array $_questions = [];
  private array $_next_ids = [];

  public function __construct(int $survey_id)
  {
    if($this->verify_id($survey_id)) 
    {
      $this->_survey_id = $survey_id;
      $this->add_options();
      $this->add_sections();
      $this->add_groups();
      $this->add_questions($survey_id);
      $this->add_next_ids();
    }
  }

  /**
   * Returns whether or not there is any survey content associated with the
   *   survey_id used to instantiate this SurveyContent instance
   * @return bool 
   */
  public function is_empty() : bool
  {
    return $this->_survey_id === null;
  }

  /**
   * Returns array of all options common to all questions in the specified survey
   * @return list< array {
   *   option_id: int unique identifier for each option,
   *   text: string how option appears in the survey form
   * } >
   */
  public function options() : array { return $this->_options; }

  /**
   * Returns array of all sections in the specified survey
   * @return array {
   *   section_id: int unique identifier for each setion,
   *   sequence: int order this section appears in the survey,
   *   name: string name of this section,
   *   collapsible: truthy/falsey value if the section can be opened/closed in the survey,
   *   intro?: string text to display at top of the section,
   *   content: list< array { 
   *      type: ('question'|'group'),
   *      id: int question ID or group id
   *   } questions/groups in this section (in order)
   * } >
   */
  public function sections() : array { return $this->_sections; }

  /**
   * Returns array of all question groups in the specified survey
   * @return array {
   *   group_id: int unique identifier for each group,
   *   name: string name as it will appear in the survey editor,
   *   content: array<int> question IDs that appear in the group (in order)
   * }
   */
  public function groups() : array { return $this->_groups; }

  /**
   * Returns the details associated with the specified group
   * @param int $group_id 
   * @return null || array {
   *   group_id: int unique identifier for each group,
   *   name: string name as it will appear in the survey editor,
   *   content: array<int> question IDs that appear in the group (in order)
   * }
   */
  public function group(int $group_id) : ?array {
    return $this->_groups[$group_id] ?? null;
  }

  /**
   * Returns array of all questions in the specified survey (and its ancestors)
   * @return array list< {
   *   id: int unique identifier for each question,
   *   type: QuestionType,
   *   wording: string how question appears in the survey,
   *   intro?: string introductory text shown before question,
   *   qualifier?: string (BOOL and SELECT only) label for optional freetext field in response,
   *   other_flag?: bool (SELECT only) if an "other" field will be included in response,
   *   other?: string (SELECT only) label for "other" field in response,
   *   info?: string (INFO only) the body of the info message,
   *   popup?: string (all but INFO) text in popup hint in the survey,
   *   layout: QuestionLayout
   *   render_in_group?: bool (INFO only),
   *   options?: array<int> (SELECT only) the list of selectable options
   * } >
   */
  public function questions() : array { return $this->_questions; }

  /**
   * Returns the details associated with the specified question
   * @param int $question_id : the survey of interest
   * @return array {
   *   id: int unique identifier for each question,
   *   type: QuestionType,
   *   wording: string how question appears in the survey,
   *   intro?: string introductory text shown before question,
   *   qualifier?: string (BOOL and SELECT only) label for optional freetext field in response,
   *   other_flag?: bool (SELECT only) if an "other" field will be included in response,
   *   other?: string (SELECT only) label for "other" field in response,
   *   info?: string (INFO only) the body of the info message,
   *   popup?: string (all but INFO) text in popup hint in the survey,
   *   layout: QuestionLayout
   *   render_in_group?: bool (INFO only),
   *   options?: array<int> (SELECT only) the list of selectable options
   */
  public function question(int $question_id) : array 
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
   * @return array 
   */
  public function as_array() : array
  {
    return [
      'options'   => $this->_options,
      'sections'  => $this->_sections,
      'groups'    => $this->_groups,
      'questions' => $this->_questions,
      'next_ids'  => $this->_next_ids,
    ];
  }

  private function verify_id($survey_id)
  {
    $query = <<<SQL
      SELECT *
        FROM tlc_srv_surveys
       WHERE survey_id=(?);
    SQL;
    $rows = MySQLFetchAllAssoc($query, 'i', $survey_id);
    return count($rows) === 1;
  }

  private function add_options()
  {
    $query = <<<SQL
      SELECT option_id, option_str as text
        FROM tlc_srv_survey_options
       WHERE survey_id=(?)
       ORDER BY option_id;
    SQL;
    $rows = MySQLFetchAllAssoc($query, 'i', $this->_survey_id);

    if($rows) {
      $this->_options = array_column($rows, 'text', 'option_id');
    }
  }

  private function add_sections()
  {
    $query = <<<SQL
      SELECT section_id, name, collapsible, intro
      FROM   tlc_srv_sections
      WHERE survey_id=(?)
      ORDER BY section_id;
    SQL;
    $rows = MySQLFetchAllAssoc($query, 'i', $this->_survey_id);
    if(!$rows) { return; }

    $this->_sections = array_column($rows,null,'section_id');

    $query = <<<SQL
      SELECT section_id, sequence, group_id, question_id
        FROM tlc_srv_section_content 
       WHERE survey_id=(?)
       ORDER BY section_id, sequence
    SQL;
    $rows = MySQLFetchAllIndexed($query,'i',$this->_survey_id);
    foreach($rows as [$section_id,$sequence,$group_id,$question_id]) {
      if(!is_null($question_id)) {
        $this->_sections[$section_id]['content'][] = ['type'=>'question', 'id'=>$question_id];
      } else {
        $this->_sections[$section_id]['content'][] = ['type'=>'group', 'id'=>$group_id];
      }
    }
  }

  private function add_groups()
  {
    $query = <<<SQL
      SELECT group_id, name
        FROM tlc_srv_question_groups
       WHERE survey_id=(?)
    SQL;
    $rows = MySQLFetchAllAssoc($query, 'i', $this->_survey_id);
    if (!$rows) { return; }

    $this->_groups = array_column($rows, null, 'group_id');
    
    $query = <<<SQL
      SELECT group_id, question_id
        FROM tlc_srv_group_content 
       WHERE survey_id=(?)
       ORDER BY group_id, sequence
    SQL;
    $rows = MySQLFetchAllIndexed($query,'i',$this->_survey_id);
    foreach($rows as [$group_id,$question_id]) {
      $this->_groups[$group_id]['content'][] = $question_id;
    }
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
      $question_id = $row['question_id'];
      $question_type = QuestionType::from($row['question_type']);

      $q = [ 
        'id'       => $question_id, 
        'type'     => $question_type,
      ];

      foreach( $question_type->fields() as $from => $to )
      {
        if(is_int($from)) { $from = $to; } // straight copy from row to question
        $q[$to] = $row[$from];
      }

      # decode the question_flags bitmap
      $flags = new QuestionFlags( $row['flags'] ?? 0 );
      $q['layout']  = $flags->layout($question_type);
      if($question_type->isIinfo()) {
        $q['render_in_group'] = $flags->render_in_group();
      }
      if($question_type->isSelect()) {
        $q['other_flag'] = $flags->has_other() ? 1 : 0;
      }

      // add question options
      if($question_type->isSelect()) {
        $query = <<<SQL
          SELECT option_id
          FROM   tlc_srv_question_options
          WHERE survey_id=? and question_id=?
          ORDER BY sequence
        SQL;
        $q['options'] = MySQLFetchColumn($query, 'ii', $survey_id,$question_id);
      }

      $this->_questions[$question_id] = $q;
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

  function add_next_ids()
  {
    $this->_next_ids = [
      'survey'   => 1 + MySQLFetchValue('select max(survey_id)   from tlc_srv_surveys'),
      'question' => 1 + MySQLFetchValue('select max(question_id) from tlc_srv_questions'),
      'option'   => 1 + MySQLFetchValue('select max(option_id)   from tlc_srv_survey_options where survey_id=(?)','i',$this->_survey_id),
    ];
  }

}