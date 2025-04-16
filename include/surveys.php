<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));


function active_survey_id()
{
  $ids = MySQLSelectValues("select id from tlc_tt_active_surveys");
  if(count($ids)>1) {
    internal_error("Multiple active surveys found in the database: ".implode(', ',$ids));
  }
  return $ids[0] ?? false;
}

function active_survey_title()
{
  $titles = MySQLSelectValues("select title from tlc_tt_active_surveys");
  if(count($titles)>1) {
    internal_error("Multiple active surveys found in the database: ".implode(', ',$titles));
  }
  return $titles[0] ?? null;
}

function all_surveys()
{
  $surveys = array();

  $draft = MySQLSelectRows( 
    'select * from tlc_tt_surveys where active is NULL order by created'
  );
  $active = MySQLSelectRows( 
    'select * from tlc_tt_surveys where active is not NULL and closed is NULL order by active'
  );
  $closed = MySqlSelectRows(
    'select * from tlc_tt_surveys where closed is not NULL order by closed'
  );

  if($draft) {
    $surveys['draft'] = array();
    foreach($draft as $survey) {
      $survey['has_pdf'] = (null !== survey_pdf_file($survey['id']));
      $surveys['draft'][] = $survey;
    }
  }
  if($active) {
    $surveys['active'] = array();
    foreach($active as $survey) {
      $survey['has_pdf'] = (null !== survey_pdf_file($survey['id']));
      $surveys['active'][] = $survey;
    }
  }
  if($closed) {
    $surveys['closed'] = array();
    foreach($closed as $survey) {
      $survey['has_pdf'] = (null !== survey_pdf_file($survey['id']));
      $surveys['closed'][] = $survey;
    }
  }

  return $surveys;
}

function survey_pdf_file($survey_id)
{
  $pdf_file = app_file("pdf/survey_$survey_id.pdf");
  if(file_exists($pdf_file)) {
    return $pdf_file;
  } else {
    return null;
  }
}

function create_new_survey($name,$clone_id,$pdf_file,&$info=null)
{
  class FailedToCreate extends \Exception {}

  $info = '';
  $new_id = null;

  try {
    MySQLBeginTransaction();

    $rc = MySQLExecute("insert into tlc_tt_surveys (title) values (?)",'s',$name);
    if(!$rc) { 
      throw new FailedToCreate('Failed to create a new entry in the database');
    }
    $new_id = MySQLInsertID();

    if($clone_id) {
      $nrows = MySQLSelectValue(
        "select count(*) from tlc_tt_survey_content where survey_id = $clone_id"
      );
      log_dev("Number of rows in cloned survey ($clone_id) = $nrows");

      if($nrows) {
        $query = <<<SQL
        INSERT INTO tlc_tt_survey_content
        SELECT 
          $new_id as survey_id,
          t.section_seq,
          t.element_seq,
          1 as revision,
          t.section_id,
          t.element_id,
          t.element_rev
        FROM
          tlc_tt_survey_content t
        WHERE
          t.survey_id = $clone_id
          AND t.revision = (
            SELECT MAX(revision)
              FROM tlc_tt_survey_content
             WHERE survey_id = t.survey_id
               AND section_seq = t.section_seq
               AND element_seq = t.element_seq
          );
        SQL;

        if(!MySQLExecute($query)) {
          throw new FailedToCreate('Failed to copy content from cloned survey');
        }
      }
    }

    if($pdf_file) {
      $tgt_file = app_file("pdf/survey_$new_id.pdf");
      if(!move_uploaded_file($pdf_file,$tgt_file)) {
        throw new FailedToCreate("Failed to upload PDF file");
      }
    }

    MySQLCommit();
  }
  catch(FailedToCreate $e)
  {
    MySQLRollback();
    $info = "Failed to create new survey (" . $e->getMessage() . ")";
    $new_id = null;
  }

  return $new_id;
}

