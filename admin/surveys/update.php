<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }
require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/surveys.php'));
require_once(app_file('include/question_flags.php'));

class FailedToUpdate extends \Exception {}

function update_survey_state($survey_id, $new_state, &$message=null)
{
  $old_state = get_survey_state($survey_id);
  if($new_state == $old_state) {
    $message = "Survey was already in $new_state state";
    return false;
  }
  switch($new_state) {
  case 'draft':
    $update = "UPDATE tlc_srv_surveys SET active=NULL, closed=NULL WHERE survey_id=?";
    break;
  case 'active':
    $update = "UPDATE tlc_srv_surveys SET active=CURRENT_TIMESTAMP, closed=NULL WHERE survey_id=?";
    break;
  case 'closed':
    $update = "UPDATE tlc_srv_surveys SET closed=CURRENT_TIMESTAMP WHERE survey_id=?";
    break;
  default:
    throw new FailedToUpdate("Invalid survey state ($new_state)");
    break;
  }
  if( MySQLExecute($update,'i',$survey_id) === false ) {
    throw new FailedToUpdate("Failed to update state for survey $survey_id to $new_state");
  }
  $message = "Updated state of survey $survey_id to $new_state";
  return true;
}

function get_survey_state($survey_id)
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

function update_survey($survey_id, $content, $title)
{
  // We want the update to be all or nothing, so wrap it in a MySQL transaction
  //   so that we can do a rollback if something goes wrong
  MySQLBeginTransaction();

  try {
    // begin by purging all data for current survey_id
    //   if we (temporarily) delete the entry from the surveys table, the foreign
    //   keys should cascade the deletion to all of the other tables.
    //
    // but first, retrieve the current data
    //  - survey content is retrieved below
    //  - user response data is cached in database
    
    $details = MySQLFetchOneAssoc('select * from tlc_srv_surveys where survey_id=$survey_id');
    if($title) {
      $details['title'] = $title;
    }

    cache_user_responses($survey_id);

    // We can now safely delete the entry from the survey table

    MySQLExecute("delete from tlc_srv_surveys where survey_id=$survey_id");

    // and start repopulating the current revision

    update_survey_details($survey_id,$details);
    update_survey_options($survey_id,$content);
    update_survey_content($survey_id,$content);

    // and reload the user responses
    
    restore_user_responses($survey_id);

    // final step is to commit the transaction
    //   if there was an exception the transaction will be rolled back in the catch block
    MySQLCommit(); 
  }
  catch(\Exception $e)
  {
    MySQLRollback();
    throw $e;
  }
}

function cache_user_responses($survey_id)
{
  log_dev("Backup responses before temporary drop of survey data");

  $tables = ['user_status','responses','response_options'];
  foreach($tables as $table) {
    $table = 'tlc_srv_' . $table;
    $cache = $table . '_cache';

    validate_cache_table($table,$cache);

    $query = "delete from $cache";
    MySQLExecute($query);

    $query = "insert into $cache select * from $table where survey_id=$survey_id";
    $rc = MySQLExecute($query);
    if($rc === false) { throw new \Exception("Failed to cache $table"); }
  }
}

function validate_cache_table($table,$cache)
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

function restore_user_responses($survey_id)
{
  log_dev("Restore responses after temporary drop of survey data");

  $table = 'tlc_srv_user_status';
  $cache = 'tlc_cache_user_sttus';
  $query = "insert into $table select * from $cache where survey_id=$survey_id";
  $rc = MySQLExecute($query);
  if($rc === false) { throw new \Exception("Failed to restore user status"); }

  $query = <<<SQL
    INSERT INTO tlc_srv_responses
           (  userid,   survey_id,   question_id,   draft,   selected,   free_text,   qualifier,   other )
    SELECT  c.userid, c.survey_id, c.question_id, c.draft, c.selected, c.free_text, c.qualifier, c.other
      FROM tlc_cache_responses c
      JOIN tlc_srv_questions q ON q.survey_id=c.survey_id AND q.question_id=c.question_id
     WHERE c.survey_id=$survey_id;
  SQL;
  $rc = MySQLExecute($query);
  if($rc === false) { throw new \Exception("Failed to restore user responses"); }

  $query = <<<SQL
    INSERT INTO tlc_srv_response_options
           (  userid,   survey_id,   question_id,   draft,   option_id )
    SELECT  c.userid, c.survey_id, c.question_id, c.draft, c.option_id
      FROM tlc_cache_response_options c
      JOIN tlc_srv_question_options q 
        ON q.survey_id=c.survey_id AND q.question_id=c.question_id AND q.option_id=c.option_id
     WHERE c.survey_id=$survey_id;
  SQL;
  $rc = MySQLExecute($query);
  if($rc === false) { throw new \Exception("Failed to restore user response options"); }
}

function update_survey_details($survey_id,$details)
{
  $parent_id = $details['parent_id'];
  $title     = $details['title'];
  $created   = $details['created'];
  // modified gets set via the default
  $active    = $details['active'];
  $closed    = $details['closed'];

  $update = <<<SQL
    INSERT into tlc_srv_surveys
           (survey_id,parent_id,title,created,active,closed)
    VALUES ($survey_id,?,?,?,?,?)
  SQL;

  $rc = MySQLExecute($update,'issss', $parent_id, $title, $created, $active, $closed);

  if( $rc === false ) {
    throw new FailedToUpdate("Failed to update title for survey $survey_id"); 
  }
}

function update_survey_options($survey_id,$content)
{
  $options = $content['options'];

  $insert = <<<SQL
    INSERT into tlc_srv_survey_options (survey_id, option_id, option_str) 
    VALUES ($survey_id,?,?)
    ON DUPLICATE KEY UPDATE option_str = values(option_str)
  SQL;

  foreach($options as $option_id => $option_str) 
  {
    if( MySQLExecute($insert,'is', $option_id, $option_str) === false) {
      throw new FailedToUpdate("Failed to update survey options ($option_id, $option_str)");
    }
  }
}

function update_survey_content($survey_id,$content)
{
  // conolidate questions into the correponding sections
  $sections = consolidate_survey_content($content);

  $insert = <<<SQL
    INSERT into tlc_srv_sections
           (survey_id, section_id, name, collapsible, intro)
    VALUES ($survey_id,?,?,?,?,?)
  SQL;

  foreach( $sections as $section ) {
    $section_id = $section['section_id'];
    $rc = MySQLExecute(
      $insert, 'iisiss',
      $section_id,
      $section['name'],
      ($section['collapsible'] ?? null) ? 1 : 0,
      $section['intro']
    );
    if($rc === false) {
      throw new FailedToUpdate("Failed to update survey sections ($section_id)");
    }

    if(array_key_exists('questions',$section)) {
      update_survey_questions($survey_id,$section_id,$section['questions']);
    }
  }
}

function update_survey_questions($survey_id,$section_id,$questions)
{
  usort($questions, fn($a,$b) => $a['sequence'] <=> $b['sequence']);

  $insert = <<<SQL
    INSERT into tlc_srv_questions
           (question_id, survey_id, wording,question_type,question_flags, other,qualifier,intro,info)
    VALUES (?,$survey_id,?,?,?,?,?,?,?)
  SQL;

  $sequence = 1;
  foreach($questions as $question) {
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
    $flags->grouped($question['grouped'] ?? "NO");

    $rc = MySQLExecute( $insert, 'ississss',
      $question_id, $wording, $type, $flags->get_bits(), $other, $qualifier, $intro, $info
    );
    if($rc === false) {
      throw new FailedToUpdate("Failed to update survey question $question_id");
    }

    update_question_map($survey_id,$question_id,$section_id,$sequence);

    if(array_key_exists('options',$question)) {
      update_question_options($survey_id,$question_id,$question['options']);
    }

    $sequence += 1;
  }
}

function update_question_map($survey_id,$question_id,$section_id,$question_seq)
{
  $insert = <<<SQL
    INSERT into tlc_srv_question_map
           (survey_id,section_id,question_seq,question_id)
    VALUES ($survey_id,$section_id,$question_seq,$question_id)
  SQL;

  $rc = MySQLExecute($insert);

  if($rc === false) {
    throw new FailedToUpdate("Failed to update question_map $question_id");
  }
}

function update_question_options($survey_id,$question_id,$options)
{
  $insert = <<<SQL
    INSERT into tlc_srv_question_options
           (survey_id,question_id,sequence,option_id)
    VALUES ($survey_id,$question_id,?,?)
  SQL;

  $sequence = 1;
  foreach($options as $option_id) {
    $rc = MySQLExecute($insert,'ii', $sequence, $option_id);
    if($rc === false) {
      throw new FailedToUpdate("Failed to update question options $question_id/$option_id");
    }
    $sequence += 1;
  }
}

function consolidate_survey_content($content)
{
  $sections = [];
  foreach($content['sections'] as $section) {
    $sid = $section['section_id'];
    $sections[$sid] = $section;
  }

  foreach($content['questions'] as $question) {
    $sid = $question['section'];
    if(isset($sections[$sid])) {
      $sections[$sid]['questions'][] = $question;
    }
  }

  foreach(array_keys($sections) as $sid) {
    if(isset($sections[$sid]['questions'])) {
      usort($sections[$sid]['questions'], fn($a,$b) => $a['sequence'] <=> $b['sequence']);
    }
  }

  return $sections;
}
