<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/question_flags.php'));

/**
 * Returns the survey ID of the active survey
 * @return null|int 
 */
function active_survey_id() : ?int 
{ 
  $ids = MySQLFetchColumn("select survey_id from tlc_srv_active_surveys");
  if(count($ids)>1) {
    internal_error("Multiple active surveys found in the database: ".implode(', ',$ids));
  }
  return $ids[0] ?? false;
}

/**
 * Returns the name of the active survey
 * @return null|string 
 */
function active_survey_title() : ?string 
{
  $titles = MySQLFetchColumn("select title from tlc_srv_active_surveys");
  if (count($titles) > 1) {
    internal_error("Multiple active surveys found in the database: " . implode(', ', $titles));
  }
  return $titles[0] ?? null;
}

/**
 * Returns an associative array containing all of the info about the specified survey
 *   that is found in the database:  
 *   - survey_id : ID of the survey iteself
 *   - id : Same as survey_id, but needed by javascript
 *   - parent_id : ID of the survey from which this survey was cloned (null if not cloned)
 *   - title : name of the survey
 *   - created : date/time the survey was created
 *   - modified  : date/time the survey content/structure was last modified
 *   - active : date/time the survey went active (null if not active or closed)
 *   - closed : date/time the survey was closed (null if still draft or active)
 * @param int $id, the survey of interest
 * @return null|array 
 */
function survey_info($id) : ?array 
{
  $info = MySQLFetchOneAssoc("select * from tlc_srv_surveys where survey_id=?", 'i', $id);
  if (!$info) { return null; }
  $info['id'] = $info['survey_id']; // need to add this as it is not from database
  return $info;
}

/**
 * Returns for each survey in the dataase an associative array containing the following
 *   info about each:
 *   - survey_id : ID of the survey iteself
 *   - parent_id : ID of the survey from which this survey was cloned (null if not cloned)
 *   - status : draft, active, or closed
 *   - title : name of the survey
 *   - created : date/time the survey was created
 *   - modified  : date/time the survey content/structure was last modified
 *   - active : date/time the survey went active (if active or closed)
 *   - closed : date/time the survey was closed (if closed)
 *  The order of the returned surveys will always be:
 *   - active survey (at most one)
 *   - draft surveys (unspecified order)
 *   - closed surveys (unspecified order)
 * @return array 
 */
function all_surveys() : array 
{
  $surveys = [];

  $active = MySQLFetchAllAssoc('select * from tlc_srv_active_surveys');
  $drafts = MySQLFetchAllAssoc('select * from tlc_srv_draft_surveys');
  $closed = MySQLFetchAllAssoc('select * from tlc_srv_closed_surveys');

  $nactive = count($active);
  if($nactive) {
    if($nactive>1) { internal_error('Multiple active surveys found'); }
    $survey = $active[0];
    $survey['status'] = 'active';
    $surveys[] = $survey;
  }
  foreach($drafts as $survey) {
    $survey['status'] = 'draft';
    $surveys[] = $survey;
  }
  foreach($closed as $survey) {
    $survey['status'] = 'closed';
    $surveys[] = $survey;
  }

  return $surveys;
}

  /**
   * Returns an array of the next ID to be used when creating a new survey, question, or option.
   *   survey:   next available survey ID (must be unique across all surveys)
   *   question: next available question ID (must be unique across all surveys)
   *   option:   next available option ID (must be unique within a given survey)
   * @param int $survey_id 
   * @return array 
   */
function next_survey_ids(int $survey_id) : array
{
  return [
    'survey'   => 1 + MySQLFetchValue('select max(survey_id)   from tlc_srv_surveys'),
    'question' => 1 + MySQLFetchValue('select max(question_id) from tlc_srv_questions'),
    'option'   => 1 + MySQLFetchValue('select max(option_id)   from tlc_srv_survey_options where survey_id=(?)','i',$survey_id),
  ];
}

/**
 * Returns a structured array containing all of the content information for the specified survey
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
 *     type: 'INFO', 'BOOL', 'SELECT_MULTI', 'SELECT_ONE', or 'FREETEXT'
 *     wording: how question appears in the survey
 *     intro: optional introductory text shown before question
 *     qualifier: (BOOL and SELECT only) label for optional freetext field in response
 *     other_flag: (SELECT only) if an "other" field will be included in response
 *     other: (SELECT only) label for "other" field in response
 *     info: (INFO only) the body of the info message
 *     popup: (all but INFO) text in popup hint in the survey
 *     layout:  how responses appear in the survey:
 *        BOOl: 'LEFT' or 'RIGHT' (checkbox location)
 *        SELECT: 'ROW', 'RCOL' or 'LCOL'
 *        default: null
 *     render_in_group: (INFO only)
 *     options: (SELECT only) the list of selectable options
 *   next_ids:
 *     survey:   next available survey ID (must be unique across all surveys)
 *     question: next available question ID (must be unique across all surveys)
 *     option:   next available option ID (must be unique within a given survey)
 * 
 * @param int $survey_id : the survey of interest
 * @return array 
 */
function survey_content(int $survey_id) : array
{
  return [
    'options'   => survey_options($survey_id),
    'sections'  => survey_sections($survey_id),
    'groups'    => survey_groups($survey_id), 
    'questions' => survey_questions($survey_id), 
    'next_ids'  => next_survey_ids($survey_id),
  ];
}

/**
 * Returns array of all options common to all questions in the specified survey
 * This includes for each option:
 *   option_id : unique identifier for each option (*array index)
 *   text : how option appears in the survey form
 * @param int $survey_id : the survey of intereset
 * @return array 
 */
function survey_options(int $survey_id) : array
{
  $query = <<<SQL
    SELECT option_id, option_str as text
      FROM tlc_srv_survey_options
     WHERE survey_id=(?)
     ORDER BY option_id;
  SQL;
  $rows = MySQLFetchAllAssoc($query, 'i', $survey_id);

  return $rows ? array_column($rows,'text','option_id') : [];
}

/**
 * Returns array of all sections in the specified survey
 * This includes for each section:
 *    section_id : unique identifier for each section (*array index) 
 *    sequence : order this section appears in the survey
 *    name : name of this section 
 *    collapsible : truthy/falsey value if the section can be opened/closed in the survey
 *    intro : optional text to display at top of the section
 *    content : array of questions/groups in this section (in order)
 *       type: 'question' or 'group'
 *       id: question ID or group ID
 * @param int $survey_id 
 * @return array 
 */
function survey_sections(int $survey_id) : array
{
  $query = <<<SQL
    SELECT section_id, name, collapsible, intro
    FROM   tlc_srv_sections
    WHERE survey_id=(?)
    ORDER BY section_id;
  SQL;
  $rows = MySQLFetchAllAssoc($query, 'i', $survey_id);
  if(!$rows) { return []; }

  $sections = array_column($rows,null,'section_id');

  $query = <<<SQL
    SELECT section_id, sequence, group_id, question_id
      FROM tlc_srv_section_content 
     WHERE survey_id=(?)
     ORDER BY section_id, sequence
  SQL;
  $rows = MySQLFetchAllIndexed($query,'i',$survey_id);
  foreach($rows as [$section_id,$sequence,$group_id,$question_id]) {
    if(!is_null($question_id)) {
      $sections[$section_id]['content'][] = ['type'=>'question', 'id'=>$question_id];
    } else {
      $sections[$section_id]['content'][] = ['type'=>'group', 'id'=>$group_id];
    }
  }

  return $sections;
}

/**
 * Returns array of all question groups in the specified survey
 * This includes for each group:
 *   group_id : unique identifier for each group
 *   name : group name as it will appear in the survey editor
 *   content : array of question IDs that appear in the group (in order)
 * @param string $survey_id : the survey of interest
 * @return array 
 */
function survey_groups(string $survey_id) : array
{
  $query = <<<SQL
    SELECT group_id, name
      FROM tlc_srv_question_groups
     WHERE survey_id=(?)
  SQL;
  $rows = MySQLFetchAllAssoc($query, 'i', $survey_id);
  if (!$rows) { return []; }

  $groups = array_column($rows, null, 'group_id');
  
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

  return $groups;
}

/**
 * Returns array of all questons in the specified survey (and its ancestors)
 * This includes for each question
 *   id : unique identifier for each question
 *   type: 'INFO', 'BOOL', 'SELECT_MULTI', 'SELECT_ONE', or 'FREETEXT'
 *   wording: how question appears in the survey
 *   intro: optional introductory text shown before question
 *   qualifier: (BOOL and SELECT only) label for optional freetext field in response
 *   other_flag: (SELECT only) if an "other" field will be included in response
 *   other: (SELECT only) label for "other" field in response
 *   info: (INFO only) the body of the info message
 *   popup: (all but INFO) text in popup hint in the survey
 *   layout:  how responses appear in the survey:
 *      BOOl: 'LEFT' or 'RIGHT' (checkbox location)
 *      SELECT: 'ROW', 'RCOL' or 'LCOL'
 *      default: null
 *   render_in_group: (INFO only)
 *   options: (SELECT only) the list of selectable options
 * @param int $survey_id : the survey of interest
 * @param array<int> $exclude : question IDs to not include in query
 * @return array 
 */
function survey_questions(int $survey_id, array $exclude = []) : array
{
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

  if(!$rows) { return array(); }

  $q_fields = [
    'INFO'         => ['wording'=>'infotag',                     'info'         ],
    'BOOL'         => ['wording', 'intro', 'qualifier',          'info'=>'popup'],
    'SELECT_MULTI' => ['wording', 'intro', 'qualifier', 'other', 'info'=>'popup'],
    'SELECT_ONE'   => ['wording', 'intro', 'qualifier', 'other', 'info'=>'popup'],
    'FREETEXT'     => ['wording', 'intro',                       'info'=>'popup']
  ];

  $questions = array();
  foreach($rows as $row) {
    $question_id = $row['question_id'];
    $question_type = $row['question_type'];

    $q = [ 
      'id'       => $question_id, 
      'type'     => $question_type,
    ];

    foreach ($q_fields[$question_type] ?? [] as $from => $to)
    {
      if(is_int($from)) { $from = $to; } // straight copy from row to question
      $q[$to] = $row[$from];
    }

    # decode the question_flags bitmap
    $flags = new QuestionFlags( $row['flags'] ?? 0 );
    $q['layout']  = $flags->layout($question_type);
    if($question_type === 'INFO') {
      $q['render_in_group'] = $flags->render_in_group();
    }
    if(str_starts_with($question_type,'SELECT')) {
      $q['other_flag'] = $flags->has_other() ? 1 : 0;
    }

    // add question options
    if($question_type==='SELECT_MULTI' || $question_type==='SELECT_ONE') {
      $query = <<<SQL
        SELECT option_id
        FROM   tlc_srv_question_options
        WHERE survey_id=? and question_id=?
        ORDER BY sequence
      SQL;
      $q['options'] = MySQLFetchColumn($query, 'ii', $survey_id,$question_id);
    }

    $questions[$question_id] = $q;
    
    // exclude this question from ancestor searches
    $exclude[] = $question_id;
  }

  // add questions found in ancestor surveys that are not in the current survey
  
  $query = <<<SQL
    SELECT parent_id from tlc_srv_surveys where survey_id=?;
  SQL;
  $parent_id = MySQLFetchValue($query,'i',$survey_id);
  if($parent_id !== null) 
  {
    $questions += survey_questions($parent_id,$exclude);
  }

  return $questions;
}
