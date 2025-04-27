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

function create_new_survey($name,$parent_id,$pdf_file,&$error=null)
{
  class FailedToCreate extends \Exception {}

  $error = '';
  $new_id = null;

  try {
    log_dev("begin try");
    MySQLBeginTransaction();

    $rc = MySQLExecute("insert into tlc_tt_surveys (title) values (?)",'s',$name);

    if(!$rc) { 
      throw new FailedToCreate('Failed to create a new entry in the database');
    }
    $new_id = MySQLInsertID();

    log_dev("new id=$new_id");

    if($parent_id) {
      $parent_rev = MySQLSelectValue(
        "select revision from tlc_tt_surveys where id = $parent_id"
      );
      if(!$parent_rev) {
        throw new FailedToCreate("Failed to find survey to clone ($parent_id)");
      }
      log_dev("parent_rev=$parent_rev");

      $rc = MySQLExecute("update tlc_tt_surveys set parent=$parent_id where id=$new_id");
      if(!$rc) {
        throw new FailedToCreate("Failed to update parent id in clone");
      }

      log_dev("begin clones $parent_id => $new_id");
      clone_survey_options($parent_id,$new_id);
      clone_survey_sections($parent_id,$new_id);
      clone_survey_elements($parent_id,$new_id);
      clone_element_options($parent_id,$new_id);
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

function clone_survey_options($parent_id,$child_id)
{
  log_dev("clone_survey_options($parent_id,$child_id)");
  $query = <<<SQL
  INSERT into tlc_tt_survey_options
  SELECT a.id, $child_id, 1, a.text
    FROM tlc_tt_survey_options a
   WHERE a.survey_id=$parent_id
     AND a.survey_rev = (
           SELECT MAX(b.survey_rev)
             FROM tlc_tt_survey_options b
            WHERE b.survey_id=$parent_id
              AND b.id = a.id )
    ;
  SQL;
  log_dev($query);
  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey options from cloned survey');
  }
}

function clone_survey_sections($parent_id,$child_id)
{
  log_dev("clone_survey_sections($parent_id,$child_id)");
  $query = <<<SQL
  INSERT into tlc_tt_survey_sections
  SELECT $child_id, 1, a.sequence, a.name, a.description, a.feedback
    FROM tlc_tt_survey_sections a
   WHERE a.survey_id=$parent_id
     AND a.survey_rev = (
           SELECT MAX(b.survey_rev)
             FROM tlc_tt_survey_sections b
            WHERE b.survey_id=$parent_id
              AND b.sequence=a.sequence )
    ;
  SQL;
  log_dev($query);
  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey sections from cloned survey');
  }
}

function clone_survey_elements($parent_id,$child_id)
{
  log_dev("clone_survey_elements($parent_id,$child_id)");
  $query = <<<SQL
  INSERT into tlc_tt_survey_elements
  SELECT a.id, $child_id, 1, 
         a.section_seq, a.sequence, a.label, 
         a.element_type, a.other, a.qualifier, a.description, a.info
    FROM tlc_tt_survey_elements a
   WHERE a.survey_id=$parent_id
     AND a.survey_rev = (
           SELECT MAX(b.survey_rev)
             FROM tlc_tt_survey_elements b
            WHERE b.survey_id=$parent_id
              AND b.id=a.id )
    ;
  SQL;
  log_dev($query);
  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey elements from cloned survey');
  }
}

function clone_element_options($parent_id,$child_id)
{
  log_dev("clone_element_options($parent_id,$child_id)");
  $query = <<<SQL
  INSERT into tlc_tt_element_options
  SELECT $child_id, 1, a.element_id, a.sequence, a.option_id, a.secondary
    FROM tlc_tt_element_options a
   WHERE a.survey_id=$parent_id
     AND a.sequence is not NULL
     AND a.survey_rev = (
           SELECT MAX(b.survey_rev)
             FROM tlc_tt_element_options b
            WHERE b.survey_id=$parent_id
              AND a.element_id=b.element_id
              AND a.option_id=b.option_id )
    ;
  SQL;
  log_dev($query);
  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey elements from cloned survey');
  }
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

