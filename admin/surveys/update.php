<?php
namespace tlc\tts;

use InvalidArgumentException;
use mysqli_sql_exception;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }
require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/question_flags.php'));

class FailedToUpdate extends \Exception {}
class ContentError extends \Exception {}

/**
 * Returns the current state of the specified survey
 * @param int $survey_id 
 * @return null|'closed'|'active'|'draft'
 */
function get_survey_state(int $survey_id) : ?string
{
  $query = <<<SQL
    SELECT
      (CASE WHEN closed IS NOT NULL THEN 'closed'
            WHEN active IS NOT NULL THEN 'active'
                                    ELSE 'draft' END) as state
    FROM tlc_srv_surveys
    WHERE survey_id=?
  SQL;
  return MySQLFetchValue($query,'i',$survey_id);
}

/** * Constructs an exception handler for use in MySQLExecuteWithExceptionHandler 
 * @param string $message 
 * @return callable 
 * @note To include a parameter in the exception string, include sprintf positional
 *       style specifiers (e.g. "Failed to include group %2$d in section %1$d")
 */ 
function build_exception_handler(string $message) : callable 
{ 
  return function(mysqli_sql_exception $e,array $p) use ($message) { 

    if($p) { $message = 'Failed to ' . sprintf($message, ...$p) . ': ' . $e->getMessage(); }
    else   { $message = $e->getMessage(); }
    log_error($message);
    throw new \Exception($message);
  }; 
}

/**
 * Choreographs te updating of the survey content/structure in the database
 * @param int $survey_id 
 * @param array $content 
 * @param string $title 
 * @return bool
 * @throws FailedToUpdate
 * @note Structure of the $content input must be consistent with the structure
 *       unpacked from the survey data json string sent by ajax
 * @note All updates are handled in a transactions so that if any update fails,
 *       all prior updates will be rolled back.
 */
function update_survey(int $survey_id, array $content, string $title) : bool
{
  // We want the update to be all or nothing, so wrap it in a MySQL transaction
  //   so that we can do a rollback if something goes wrong
  MySQLBeginTransaction();

  try {
    // begin by purging all data for current survey_id
    //   if we (temporarily) delete the entry from the surveys table, the foreign
    //   keys should cascade the deletion to all of the other tables.
    
    // but first, retrieve the current survey metadata
    $details = MySQLFetchOneAssoc('select * from tlc_srv_surveys where survey_id=?','i',$survey_id);
    if($title) {
      $details['title'] = $title;
    }

    // and caching all current participant response data
    $cached = cache_user_responses($survey_id);

    // We can now safely delete the entry from the survey table
    MySQLExecute('delete from tlc_srv_surveys where survey_id=?','i',$survey_id);

    // and start repopulating the current revision
    //  note that foreign keys drive much of the ordering of the following calls
    //  groups -> surveys
    //  section content -> sections, questions, groups
    //  group content -> section content, questions

    // survey details has no foreign keys
    add_survey_details($survey_id,$details);

    // survey options has FK to survey_id
    add_survey_options($survey_id,$content['options']??[]);

    // survey questions has FK to survey_id
    //   question options has FK to survey_id, option_id
    add_survey_questions($survey_id,$content['questions']??[]);

    // survey sections has FK to survey_id
    //   sections have FK to survey_id
    //   groups have FK to survey_id
    //   section content has FK to survey_id, section_id, group_id, question_id
    //   group content has FK to survey_id, group_id, question_id
    add_survey_content($survey_id,$content);

    // and reload the user responses
    if($cached) { restore_user_responses($survey_id); }

    // final step is to commit the transaction
    //   if there was an exception the transaction will be rolled back in the catch block
    MySQLCommit(); 
    return true;
  }
  catch(\Exception $e)
  {
    MySQLRollback();
    return false;
  }
}

/**
 * Validates that the specified caching table in the dataabase has the same columns as
 *   the table it is caching.
 * @param mixed $table 
 * @param mixed $cache 
 * @return void 
 * @note On failure to validate, internal_error is called to terminate the script
 */
function validate_cache_table(string $table,string $cache) : void
{
  log_dev("...validating cache table $cache");

  $query = <<<SQL
    SELECT count(*) FROM (
      SELECT column_name,ordinal_position,data_type,count(*) AS test FROM ( 
        SELECT 'source' AS context,column_name,ordinal_position,data_type FROM information_schema.columns WHERE table_name=?
        UNION
        SELECT 'cache' AS context,column_name,ordinal_position,data_type FROM information_schema.columns WHERE table_name=?
      ) column_map
      GROUP BY column_map.column_name,column_map.ordinal_position,column_map.data_type
    ) column_degeneracy
    WHERE column_degeneracy.test != 2
  SQL;
  $mismatch_count = MySQLFetchValue($query,'ss',$table,$cache);
  if($mismatch_count !== 0) {
    internal_error("Cache table $cache has $mismatch_count columns differences from $table");
  }
}

/**
 * Caches all user responses during survey update
 * @param int $survey_id 
 * @return bool
 * @throws FailedToUpdate on mysqli exception
 */
function cache_user_responses(int $survey_id) : bool
{
  log_dev("Backup responses before temporary drop of survey data");

  $updated_lines = 0;
  $tables = ['user_status','responses','response_options'];
  foreach($tables as $table) {
    $table = 'tlc_srv_' . $table;
    $cache = $table . '_cache';

    validate_cache_table($table,$cache);

    MySQLExecute("delete from $cache");

    $updated_lines += MySQLExecuteWithExceptionHandler(
      "insert into $cache select * from $table where survey_id=?",
      build_exception_handler("cache table $table for survey $survey_id"),
      'i',
      $survey_id 
    );
  }
  return $updated_lines > 0;
}

/**
 * Uncaches all user responses after a survey update
 * @param int $survey_id 
 * @return void 
 * @throws FailedToUpdate on mysqli exception
 */
function restore_user_responses(int $survey_id) : void
{
  log_dev("Restore responses after temporary drop of survey data");

  $query = <<<SQL
    INSERT INTO tlc_srv_user_status
    SELECT * FROM tlc_srv_user_status_cache
     WHERE survey_id=?
  SQL;
  MySQLExecuteWithExceptionHandler(
    $query,
    build_exception_handler("restore user status for survey $survey_id"),
    'i', $survey_id
  );

  $query = <<<SQL
    INSERT INTO tlc_srv_responses
           (  userid,   survey_id,   question_id,   draft,   selected,   free_text,   qualifier,   other )
    SELECT  c.userid, c.survey_id, c.question_id, c.draft, c.selected, c.free_text, c.qualifier, c.other
      FROM tlc_srv_responses_cache c
      JOIN tlc_srv_questions q ON q.survey_id=c.survey_id AND q.question_id=c.question_id
     WHERE c.survey_id=?
  SQL;
  MySQLExecuteWithExceptionHandler(
    $query,
    build_exception_handler("restore question responses for survey $survey_id"),
    'i', $survey_id
  );

  $query = <<<SQL
    INSERT INTO tlc_srv_response_options
           (  userid,   survey_id,   question_id,   draft,   option_id )
    SELECT  c.userid, c.survey_id, c.question_id, c.draft, c.option_id
      FROM tlc_srv_response_options_cache c
      JOIN tlc_srv_question_options qo 
        ON qo.survey_id=c.survey_id AND qo.question_id=c.question_id AND qo.option_id=c.option_id
     WHERE c.survey_id=?
  SQL;
  MySQLExecuteWithExceptionHandler(
    $query,
    build_exception_handler("restore question responses for survey $survey_id"),
    'i', $survey_id
  );
}

/**
 * Updates the metadata for the specified survey
 * @param int $survey_id 
 * @param array $details Survey metadata
 * @return void 
 * @throws FailedToUpdate on mysqli exception
 */
function add_survey_details(int $survey_id,array $details) : void
{
  $parent_id = $details['parent_id'];
  $title     = $details['title'];
  $created   = $details['created'];
  $active    = $details['active'];
  $closed    = $details['closed'];

  $update = <<<SQL
    INSERT into tlc_srv_surveys
           (survey_id,parent_id,title,created,active,closed)
    VALUES (?,?,?,?,?,?)
  SQL;
  MySQLExecuteWithExceptionHandler(
    $update,
    build_exception_handler("update details for survey $survey_id"),
    'iissss', 
    $survey_id, $parent_id, $title, $created, $active, $closed
  );
}

/**
 * Updates the option values shared across the survey
 * @param int $survey_id 
 * @param array{int,string} $options Survey options
 * @return void 
 * @throws FailedToUpdate on mysqli exception
 */
function add_survey_options(int $survey_id,array $options) : void
{
  $insert_option = new MySQLPreparedExec(
    <<<SQL
      INSERT into tlc_srv_survey_options (survey_id, option_id, option_str) 
      VALUES (?,?,?)
      ON DUPLICATE KEY UPDATE option_str = values(option_str)
    SQL, 'iis',
    build_exception_handler('update option %2$d for survey %1$d')
  );
  foreach($options as $option_id => $option_str) 
  {
    $insert_option->run($survey_id, $option_id, $option_str);
  }
}

/**
 * Updates the survey question data
 * @param int $survey_id 
 * @param array $questions Questions data
 * @return void 
 * @throws FailedToUpdate on mysqli exception
 */
function add_survey_questions(int $survey_id,array $questions) : void
{
  $insert_question = new MySQLPreparedExec(
    <<<SQL
      INSERT into tlc_srv_questions
             (question_id, survey_id, wording,question_type,question_flags, other,qualifier,intro,info)
      VALUES (?,?,?,?,?,?,?,?,?)
    SQL, 'iississss',
    build_exception_handler('update question %2$d for survey %1$d')
  );

  $insert_option = new MySQLPreparedExec(
    'INSERT into tlc_srv_question_options (survey_id,question_id,sequence,option_id) VALUES (?,?,?,?)',
    'iiii',
    build_exception_handler('add option %4$d as choice %3$d for question %2$d of survey %1$d') 
  );

  foreach($questions as $question) 
  {
    $question_id = $question['id'];

    $type        = $question['type'];
    $wording     = $question['wording'] ?? $question['infotag'] ?? null;
    $qualifier   = $question['qualifier'] ?? null;
    $intro       = $question['intro'] ?? null;
    $info        = $question['info'] ?? $question['popup'] ?? null;

    $other_flag  = $question['other_flag'] ?? false;
    $other       = ($other_flag ? ($question['other'] ?? null) : null);

    # encode the question_flags bitmap
    $flags = new QuestionFlags();
    $flags->layout($type, $question['layout']??"");
    $flags->has_other($other_flag);

    $insert_question->run(
      $question_id, $survey_id,
      $wording, $type, $flags->get_bits(), 
      $other, $qualifier, $intro, $info
    );

    $options = $question['options'] ?? [];
    foreach($options as $index=>$option_id) {
      $insert_option->run($survey_id,$question_id,1+$index,$option_id);
    }
  }
}

/**
 * Updates the survey content
 * @param int $survey_id 
 * @param array $content 
 * @return void 
 * @throws FailedToUpdate on missing content or mysqli exception
 */
function add_survey_content(int $survey_id,array $content) : void
{
  $insert_section = new MySQLPreparedExec(
    'INSERT into tlc_srv_sections (survey_id, section_id, name, collapsible, intro) VALUES (?,?,?,?,?)',
    'iisis',
    build_exception_handler('update details for section %2$d for survey %1$d')
  );

  $sections = $content['sections'] ?? [];

  foreach($sections as $section) { 
    $section_id   = $section['section_id'];
    $section_name = $section['name'];
    $collapsible  = ($section['collapsible'] ?? false) ? 1 : 0;
    $intro        = $section['intro'] ?? '';

    $insert_section->run($survey_id, $section_id, $section_name, $collapsible, $intro);

    add_section_content($survey_id, $section_id, $content);
  }
}

/**
 * Updates the section content data
 * @param int $survey_id 
 * @param int $section_id
 * @param array $content Survey data
 * @return void 
 */
function add_section_content(int $survey_id, int $section_id, array $content) : void
{
  $section = $content['sections'][$section_id] ?? [];
  $section_content = $section['content'] ?? [];
  
  $add_question_to_section = new MySQLPreparedExec(
    'INSERT into tlc_srv_section_content (survey_id,section_id,sequence,question_id) VALUES (?,?,?,?)',
    'iiii', 
    build_exception_handler('add question %4$d as element %3$d in section %2$d of survey %1$d')
  );

  $insert_question_group = new MySQLPreparedExec(
    'INSERT into tlc_srv_question_groups (survey_id,group_id,name) VALUES (?,?,?)',
    'iis',
    build_exception_handler('add group %2$d (%3$d) to survey %1$d')
  );

  $add_group_to_section = new MySQLPreparedExec(
    'INSERT into tlc_srv_section_content (survey_id,section_id,sequence,group_id) VALUES (?,?,?,?)',
    'iiii', 
    build_exception_handler('add group %4$d as element %3$d in section %2$d of survey %1$d')
  );


  $add_question_to_group = new MySQLPreparedExec(
    'INSERT into tlc_srv_group_content (survey_id,group_id,sequence,question_id) VALUES (?,?,?,?)',
    'iiii',
    build_exception_handler('add question $4$d in position %3$d in group %2$d of survey %1$d')
  );

  foreach($section_content as $section_index=>$item) {
    $section_sequence = 1 + $section_index;
    $item_type = $item['type'];
    $item_id   = $item['id'];
    if($item_type === 'question') 
    {
      $question_id = $item_id;
      $add_question_to_section->run($survey_id,$section_id,$section_sequence,$question_id);
    } 
    elseif($item_type === 'group') 
    {
      $group_id = $item_id;
      $group = $content['groups'][$group_id] ?? null;
      if(!$group) { 
        $error = "Missing group $group_id data in content array";
        log_error($error);
        throw new ContentError($error);
      }

      // need to add the group before adding its ID to the section content to satisfy FK constraints
      //   1. Add survey_id, group_id, name to question groups (FK on surveys)
      //   2. Add survey_id, section_id, sequence, group_id to section content (FK on question groups)
      //   3. Add survey_id, group_id, sequence, question_id to group content (FK on section content)
      $insert_question_group->run($survey_id, $group_id, $group['name']);
      $add_group_to_section->run($survey_id,$section_id,$section_sequence,$group_id);

      foreach( $group['content'] as $group_index=>$question_id ) {
        $add_question_to_group->run($survey_id, $group_id,1+$group_index,$question_id);
      }
    }
  }
}