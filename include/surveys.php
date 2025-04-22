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

function survey_info($id)
{
  return MySQLSelectRow("select * from tlc_tt_surveys where id=?",'i',$id);
}

function all_surveys()
{
  $surveys = array(
    'active' => MySQLSelectRows('select * from tlc_tt_active_surveys'),
    'draft'  => MySQLSelectRows('select * from tlc_tt_draft_surveys'),
    'closed' => MySQLSelectRows('select * from tlc_tt_closed_surveys'),
  );

  foreach($surveys as &$t) {
    foreach($t as &$s) {
      $s['has_pdf'] = (null !== survey_pdf_file($s['id']));
    }
  }
  $nactive = count($surveys['active']);
  if($nactive) {
    if($nactive>1) { internal_error('Multiple active surveys found'); }
    $surveys['active'] = $surveys['active'][0];
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

function create_new_survey($name,$clone_id,$pdf_file,&$error=null)
{
  class FailedToCreate extends \Exception {}

  $error = '';
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
    $error = "Failed to create new survey (" . $e->getMessage() . ")";
    $new_id = null;
  }

  return $new_id;
}

function update_survey($id,$name,$pdf_action,$new_pdf_file,&$error=null)
{
  class FailedToUpdate extends \Exception {}

  $error = '';
  $errid = bin2hex(random_bytes(3));

  try {
    if($name) 
    {
      MySQLBeginTransaction();
      $rc = MySQLExecute('update tlc_tt_surveys set title=? where id=?','si',$name,$id);
      if($rc === false) {
        log_error("[$errid] Failed to update entry ($id,$name)");
        throw FailedToUpdate("updating name");
      }
    }

    $pdf_path = app_file("pdf/survey_$id.pdf");

    if($pdf_action === 'drop' || $pdf_action === 'replace') {
      if(file_exists($pdf_path)) {
        if(!unlink($pdf_path)) {
          log_error("[$errid] Failed to unlink $pdf_path");
          throw FailedToUpdate("drop existing PDF file");
        }
      }
    }

    if($pdf_action === 'add' || $pdf_action === 'replace') {
      if(!move_uploaded_file($new_pdf_file,$pdf_path)) {
        log_error("[$errid] Failed to save updloded PDF to $pdf_path");
        throw FailedToUpdate("updating PDF file");
      }
    }

    if($name) { MySQLCommit(); }
  }
  catch(FailedToUpdate $e)
  {
    if($name) { MySQLRollback(); }
    $error = "Failed to update survey. Please report error $errid to a tech admin";
  }

  return strlen($error) == 0;
}

