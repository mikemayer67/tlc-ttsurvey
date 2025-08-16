<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }
require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/strings.php'));
require_once(app_file('include/surveys.php'));

class FailedToCreate extends \Exception {}

function create_new_survey($name,$parent_id,$pdf_file,&$error=null) 
{
  $error = '';
  $survey_id = null;

  try {
    MySQLBeginTransaction();

    $max_id = MySQLSelectValue("select max(survey_id) from tlc_tt_survey_revisions");
    $survey_id = $max_id ? 1 + $max_id : 1;

    $title_sid = strings_find_or_create($name);
    
    $rc = MySQLExecute(
      "insert into tlc_tt_survey_status (survey_id,parent_id) values (?,?)",
      'ii', $survey_id, $parent_id
    );
    if(!$rc) { 
      throw new FailedToCreate('Failed to create a new survey status entry in the database');
    }

    $rc = MySQLExecute(
      "insert into tlc_tt_survey_revisions (survey_id,survey_rev,title_sid) values (?,1,?)",
      'ii', $survey_id, $title_sid
    );
    if(!$rc) { 
      throw new FailedToCreate('Failed to create a new survey entry in the database');
    }

    if($parent_id) 
    {
      $parent_rev = MySQLSelectValue("select survey_rev from tlc_tt_surveys where survey_id=$parent_id");
      if(!$parent_id) {
        throw new FailedToCreate("Failed to find survey_rev for parent survey $parent_id");
      }

      clone_survey_options($survey_id,$parent_id,$parent_rev);
      clone_survey_sections($survey_id,$parent_id,$parent_rev);
      clone_survey_questions($survey_id,$parent_id,$parent_rev);
    }

    // we don't want to store the updloaded pdf file  until after we've updated the 
    //   database entries so that we don't need to revert this if there is a failure
    if($pdf_file) {
      $tgt_file = Surveys::pdf_path($survey_id);
      if(!move_uploaded_file($pdf_file,$tgt_file)) {
        throw new FailedToCreate("Failed to move upload PDF file");
      }
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

function clone_survey_options($child_id,$parent_id,$parent_rev)
{
  $query = <<<SQL
    INSERT into tlc_tt_survey_options
    SELECT $child_id, 1, option_id, text_sid
      FROM tlc_tt_survey_options
     WHERE survey_id=$parent_id and survey_rev=$parent_rev
  SQL;

  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey options from cloned survey');
  }
}

function clone_survey_sections($child_id,$parent_id,$parent_rev)
{
  $query = <<<SQL
    INSERT into tlc_tt_survey_sections
    SELECT $child_id, 1, sequence, name_sid, labeled, description_sid, feedback_sid
      FROM tlc_tt_survey_sections
     WHERE survey_id=$parent_id and survey_rev=$parent_rev
  SQL;

  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey sections from cloned survey');
  }
}


function clone_survey_questions($child_id,$parent_id,$parent_rev)
{
  $query = <<<SQL
    INSERT into tlc_tt_survey_questions
    SELECT question_id, $child_id, 1, 
           wording_sid, question_type, multiple, 
           other_sid, qualifier_sid, description_sid, info_sid
      FROM tlc_tt_survey_questions
     WHERE survey_id=$parent_id and survey_rev=$parent_rev
  SQL;

  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey questions from cloned survey');
  }

  $query = <<<SQL
    INSERT into tlc_tt_question_map
    SELECT $child_id, 1, section_seq, question_seq, question_id
      FROM tlc_tt_question_map
     WHERE survey_id=$parent_id and survey_rev=$parent_rev
  SQL;

  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy question map from cloned survey');
  }

  $query = <<<SQL
    INSERT into tlc_tt_question_options
    SELECT $child_id, 1, question_id, secondary, sequence, option_id
      FROM tlc_tt_question_options
     WHERE survey_id=$parent_id and survey_rev=$parent_rev
  SQL;

  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy question options from cloned survey');
  }
}

