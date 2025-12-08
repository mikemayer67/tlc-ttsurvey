<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }
require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/strings.php'));
require_once(app_file('include/surveys.php'));

class FailedToCreate extends \Exception {}

function create_new_survey($name,$parent_id,&$error=null) 
{
  $error = '';
  $survey_id = null;

  try {
    MySQLBeginTransaction();

    $max_id = MySQLSelectValue("select max(survey_id) from tlc_tt_surveys");
    $survey_id = $max_id ? 1 + $max_id : 1;

    $title_sid = strings_find_or_create($name);
    
    $rc = MySQLExecute(
      "insert into tlc_tt_surveys (survey_id,parent_id,title_sid) values (?,?,?)",
      'iii', $survey_id, $parent_id, $title_sid
    );
    if(!$rc) { 
      throw new FailedToCreate('Failed to create a new survey status entry in the database');
    }

    if($parent_id) 
    {
      clone_survey_options($survey_id,$parent_id);
      clone_survey_sections($survey_id,$parent_id);
      clone_survey_questions($survey_id,$parent_id);
    }

    MySQLCommit();
  }
  catch(FailedToCreate $e)
  {
    MySQLRollback();
    $error = "Failed to create new survey (" . $e->getMessage() . ")";
    $survey_id = null;
  }

  return $survey_id;
}

function clone_survey_options($child_id,$parent_id)
{
  $query = <<<SQL
    INSERT into tlc_tt_survey_options
    SELECT $child_id, option_id, text_sid
      FROM tlc_tt_survey_options
     WHERE survey_id=$parent_id
  SQL;

  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey options from cloned survey');
  }
}

function clone_survey_sections($child_id,$parent_id)
{
  $query = <<<SQL
    INSERT into tlc_tt_survey_sections
    SELECT $child_id, section_id, sequence, name_sid, collapsible, intro_sid, feedback_sid
      FROM tlc_tt_survey_sections
     WHERE survey_id=$parent_id
  SQL;

  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey sections from cloned survey');
  }
}


function clone_survey_questions($child_id,$parent_id)
{
  $query = <<<SQL
    INSERT into tlc_tt_survey_questions
    SELECT question_id, $child_id, 
           wording_sid, question_type, question_flags,
           other_sid, qualifier_sid, intro_sid, info_sid
      FROM tlc_tt_survey_questions
     WHERE survey_id=$parent_id
  SQL;

  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey questions from cloned survey');
  }

  $query = <<<SQL
    INSERT into tlc_tt_question_map
    SELECT $child_id, section_id, question_seq, question_id
      FROM tlc_tt_question_map
     WHERE survey_id=$parent_id
  SQL;

  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy question map from cloned survey');
  }

  $query = <<<SQL
    INSERT into tlc_tt_question_options
    SELECT $child_id, question_id, sequence, option_id
      FROM tlc_tt_question_options
     WHERE survey_id=$parent_id
  SQL;

  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy question options from cloned survey');
  }
}

