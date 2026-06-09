<?php
namespace tlc\tts;

use mysqli_sql_exception;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }
require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/surveys.php'));

class FailedToCreate extends \Exception {}

/**
 * Creates a new survey entry in the database
 * @param string $name 
 * @param null|int $parent_id ID of existing survey to clone
 * @param null|string &$error reason for failure if new survey was not created
 * @return null|int ID of new survey (or null on failure)
 */
function create_new_survey(string $name,?int $parent_id,?string &$error=null) : ?int
{
  $error = '';
  $survey_id = null;

  try {
    MySQLBeginTransaction();

    $max_id = MySQLFetchValue("select max(survey_id) from tlc_srv_surveys");
    $survey_id = $max_id ? 1 + $max_id : 1;

    $insert = new MySQLPreparedExec(
      "insert into tlc_srv_surveys (survey_id,parent_id,title) values (?,?,?)",
      'iis', onException:'die', rollbackOnException:true
    );
    $insert->run($survey_id, $parent_id, $name);

    if($parent_id) { clone_survey($survey_id,$parent_id); }

    MySQLCommit();
  }
  catch(FailedToCreate $e)
  {
    MySQLRollback(safe:true);
    $error = "Failed to create new survey (" . $e->getMessage() . ")";
    $survey_id = null;
  }

  return $survey_id;
}

/**
 * Clones all of the survey content form parent to child
 * @param int $child_id ID of the new survey to clone into
 * @param int $parent_id ID of the existing survey from which to clone
 * @return void 
 * @throws FailedToCreate on MySQL exception being raised
 */
function clone_survey(int $child_id,int $parent_id) : void
{
  $query = <<<SQL
    INSERT into tlc_srv_survey_options
    SELECT ?, option_id, text_sid
      FROM tlc_srv_survey_options
     WHERE survey_id=?
  SQL;
  $insert = new MySQLPreparedExec($query,'ii',onException:'fail');
  $result = $insert->run($child_id,$parent_id);
  if($result === false) {
    throw new FailedToCreate('Failed to copy survey options from cloned survey');
  }

  $query = <<<SQL
    INSERT into tlc_srv_sections
    SELECT ?, section_id, name, collapsible, intro
      FROM tlc_srv_sections
     WHERE survey_id=?
  SQL;
  $insert = new MySQLPreparedExec($query,'ii',onException:'fail');
  $result = $insert->run($child_id,$parent_id);
  if($result === false) {
    throw new FailedToCreate('Failed to copy survey sections from cloned survey');
  }

  $query = <<<SQL
    INSERT into tlc_srv_questions
    SELECT question_id, ?, wording, question_type, question_flags, other, qualifier, intro, info
      FROM tlc_srv_questions
     WHERE survey_id=?
  SQL;
  $insert = new MySQLPreparedExec($query,'ii',onException:'fail');
  $result = $insert->run($child_id,$parent_id);
  if($result === false) {
    throw new FailedToCreate('Failed to copy survey questions from cloned survey');
  }

  $query = <<<SQL
    INSERT into tlc_srv_question_map
    SELECT ?, section_id, question_seq, question_id
      FROM tlc_srv_question_map
     WHERE survey_id=?
  SQL;
  $insert = new MySQLPreparedExec($query,'ii',onException:'fail');
  $result = $insert->run($child_id,$parent_id);
  if($result === false) {
    throw new FailedToCreate('Failed to copy question map from cloned survey');
  }

  $query = <<<SQL
    INSERT into tlc_srv_question_options
    SELECT ?, question_id, sequence, option_id
      FROM tlc_srv_question_options
     WHERE survey_id=?
  SQL;
  $insert = new MySQLPreparedExec($query,'ii',onException:'fail');
  $result = $insert->run($child_id,$parent_id);
  if($result === false) {
    throw new FailedToCreate('Failed to copy question options from cloned survey');
  }
}

