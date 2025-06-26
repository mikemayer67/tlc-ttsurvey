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
  $info = MySQLSelectRow("select * from tlc_tt_surveys where id=?",'i',$id);
  if(survey_pdf_file($id)) {
    $info['has_pdf'] = true;
  }
  return $info;
}

function all_surveys()
{
  $surveys = [];

  $active = MySQLSelectRows('select * from tlc_tt_active_surveys');
  $drafts = MySQLSelectRows('select * from tlc_tt_draft_surveys');
  $closed = MySQLSelectRows('select * from tlc_tt_closed_surveys');

  $nactive = count($active);
  if($nactive) {
    if($nactive>1) { internal_error('Multiple active surveys found'); }
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

  foreach( $surveys as &$survey ) {
    $survey['has_pdf'] = (null !== survey_pdf_file($survey['id']));
  }

  return $surveys;
}

function survey_content($survey_id, $survey_rev=NULL)
{
  $rval = array();

  // current survey revision

  if(is_null($survey_rev)) {
    $survey_rev = MySQLSelectValue('select revision from tlc_tt_surveys where id=(?)', 'i', $survey_id);
    if(!$survey_rev) { internal_error("Cannot find revision for survey_id=$survey_id"); }
  }
  $rval['rev'] = $survey_rev;

  // current survey options

  $options = array();

  $query = <<<SQL
    SELECT a.id, a.text
      FROM tlc_tt_survey_options a
      JOIN ( 
        SELECT survey_id, id, max(survey_rev) as rev 
          FROM tlc_tt_survey_options
         WHERE survey_id=(?) AND survey_rev<=(?) 
      GROUP BY survey_id,id
    ) f
    WHERE a.survey_id=f.survey_id AND a.id=f.id AND a.survey_rev=f.rev
    ORDER BY a.id;
  SQL;
  $rows = MySQLSelectRows($query, 'ii', $survey_id, $survey_rev);
  if($rows) { 
    foreach($rows as $row) {
      $options[$row['id']] = $row['text'];
    }
  }
  $rval['options'] = $options;

  // sections associated with the current revision of the survey

  $query = <<<SQL
    SELECT a.* 
      FROM tlc_tt_survey_sections a
      JOIN ( 
        SELECT survey_id,sequence,max(survey_rev) as max_rev 
          FROM tlc_tt_survey_sections
         WHERE survey_id=(?) AND survey_rev<=(?) 
      GROUP BY survey_id, sequence
    ) f
    WHERE a.survey_id = f.survey_id AND a.sequence=f.sequence AND a.survey_rev=f.max_rev AND name is not NULL
    ORDER BY a.sequence;
  SQL;
  $rows = MySQLSelectRows($query, 'ii', $survey_id, $survey_rev);
  if(!$rows) { return $rval; }

  $sections = array();

  foreach($rows as $row) {
    $sequence = $row['sequence'];
    $sections[$sequence] = [
      'name'        => $row['name'],
      'labeled'     => $row['show_name'],
      'description' => $row['description'],
      'feedback'    => $row['feedback'],
    ];
  }
  $rval['sections'] = $sections;

  // questions associated with the current revision of the survey

  $questions = array();

  $query = <<<SQL
    SELECT a.* 
      FROM tlc_tt_survey_questions a
      JOIN (
        SELECT survey_id, id, max(survey_rev) as rev
          FROM tlc_tt_survey_questions
         WHERE survey_id=(?) AND survey_rev<=(?)
      GROUP BY survey_id, id
    ) f
    WHERE a.survey_id=f.survey_id AND a.id=f.id and a.survey_rev=rev
    ORDER BY a.section, a.sequence;
  SQL;

  $rows = MySQLSelectRows($query, 'ii', $survey_id, $survey_rev);
  if($rows) { 
    foreach($rows as $row) {
      $id = $row['id'];
      $type = $row['question_type'];
      $question = array();
      if($row['sequence']) {
        $question['section']  = $row['section'];
        $question['sequence'] = $row['sequence'];
      }
      switch($type) {
      case 'INFO':
        $question['type']        = $type;
        $question['info']        = $row['info'];
        $question['infotag']     = $row['wording'];
        break;
      case 'BOOL':
        $question['type']        = $type;
        $question['wording']     = $row['wording'];
        $question['description'] = $row['description'];
        $question['qualifier']   = $row['qualifier'];
        $question['popup']       = $row['info'];
        break;
      case 'OPTIONS':
        $question['type']        = $row['multiple'] ? 'SELECT_MULTI' : 'SELECT_ONE';
        $question['wording']     = $row['wording'];
        $question['description'] = $row['description'];
        $question['qualifier']   = $row['qualifier'];
        $question['other']       = $row['other'];
        $question['options']     = array();
        $question['popup']       = $row['info'];
        break;
      case 'FREETEXT':
        $question['type']        = $type;
        $question['wording']     = $row['wording'];
        $question['description'] = $row['description'];
        $question['popup']       = $row['info'];
        break;
      }

      $questions[$id] = $question;
    }
  }

  // add options to questions as appropriate
  
  $query = <<<SQL
    SELECT a.question_id, a.sequence, a.option_id, a.secondary
      FROM tlc_tt_question_options a
      JOIN (
        SELECT survey_id, question_id, sequence, max(survey_rev) as rev
          FROM tlc_tt_question_options 
         WHERE survey_id=(?) AND survey_rev<=(?)
      GROUP BY survey_id, question_id, sequence
    ) f
    WHERE a.survey_id=f.survey_id AND a.question_id = f.question_id AND a.sequence = f.sequence AND a.survey_rev = rev
    ORDER BY a.question_id, a.sequence;
  SQL;

  $rows = MySQLSelectRows($query, 'ii', $survey_id, $survey_rev);
  if($rows) { 
    foreach ($rows as $row) {
      $qid = $row['question_id'];
      if(!isset($questions[$qid]))            { internal_error("Options found for non-existent questions"); }
      if(!isset($questions[$qid]['options'])) { internal_error("Options found on non-options type question"); }
      $questions[$qid]['options'][] = [ $row['option_id'], $row['secondary'] ];
    }
  }

  $rval['questions'] = $questions;

  // add next IDs

  $rval['next_ids'] = next_ids($survey_id);
  log_dev("update_surveys:: next_ids = ".print_r($rval['next_ids'],true));

  return $rval;
}

function next_ids($survey_id) 
{
  return [
    'survey'   => 1 + MySQLSelectValue('select max(id) from tlc_tt_surveys'),
    'question' => 1 + MySQLSelectValue('select max(id) from tlc_tt_survey_questions'),
    'option'   => 1 + MySQLSelectValue('select max(id) from tlc_tt_survey_options where survey_id=(?)', 'i',$survey_id),
  ];
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
    MySQLBeginTransaction();

    $query = "insert into tlc_tt_surveys (title) values (?)";
    $rc = MySQLExecute($query,'s',$name);

    if(!$rc) { 
      throw new FailedToCreate('Failed to create a new entry in the database');
    }
    $new_id = MySQLInsertID();

    if($parent_id) {
      $query = "select revision from tlc_tt_surveys where id = $parent_id";
      $parent_rev = MySQLSelectValue($query);
      if(!$parent_rev) {
        throw new FailedToCreate("Failed to find survey to clone ($parent_id)");
      }

      $query = "update tlc_tt_surveys set parent=$parent_id where id=$new_id";
      $rc = MySQLExecute($query);
      if(!$rc) {
        throw new FailedToCreate("Failed to update parent id in clone");
      }

      clone_survey_options($parent_id,$new_id);
      clone_survey_sections($parent_id,$new_id);
      clone_survey_questions($parent_id,$new_id);
      clone_question_options($parent_id,$new_id);
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
  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey options from cloned survey');
  }
}

function clone_survey_sections($parent_id,$child_id)
{
  $query = <<<SQL
  INSERT into tlc_tt_survey_sections
  SELECT $child_id, 1, a.section, a.name, a.show, a.description, a.feedback
    FROM tlc_tt_survey_sections a
   WHERE a.survey_id=$parent_id
     AND a.survey_rev = (
           SELECT MAX(b.survey_rev)
             FROM tlc_tt_survey_sections b
            WHERE b.survey_id=$parent_id
              AND b.section=a.section )
    ;
  SQL;
  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey sections from cloned survey');
  }
}

function clone_survey_questions($parent_id,$child_id)
{
  $query = <<<SQL
  INSERT into tlc_tt_survey_questions
  SELECT a.id, $child_id, 1, 
         a.section, a.sequence, a.wording, 
         a.question_type, a.multiple, a.other, a.qualifier, a.description, a.info
    FROM tlc_tt_survey_questions a
   WHERE a.survey_id=$parent_id
     AND a.survey_rev = (
           SELECT MAX(b.survey_rev)
             FROM tlc_tt_survey_questions b
            WHERE b.survey_id=$parent_id
              AND b.id=a.id )
    ;
  SQL;
  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey questions from cloned survey');
  }
}

function clone_question_options($parent_id,$child_id)
{
  $query = <<<SQL
  INSERT into tlc_tt_question_options
  SELECT $child_id, 1, a.question_id, a.sequence, a.option_id, a.secondary
    FROM tlc_tt_question_options a
   WHERE a.survey_id=$parent_id
     AND a.sequence is not NULL
     AND a.survey_rev = (
           SELECT MAX(b.survey_rev)
             FROM tlc_tt_question_options b
            WHERE b.survey_id=$parent_id
              AND a.question_id=b.question_id
              AND a.option_id=b.option_id )
    ;
  SQL;
  if(!MySQLExecute($query)) {
    throw new FailedToCreate('Failed to copy survey questions from cloned survey');
  }
}

function null_on_empty($x) { return $x==='' ? null : $x; }

function update_survey($id,$rev,$name,$pdf_action,$new_pdf_file,$new_content,&$error=null)
{
  class FailedToUpdate extends \Exception {}

  $error = '';
  $errid = bin2hex(random_bytes(3));

  $has_change = false;
  $cur_content = survey_content($id);

  // TODO: Revision tracking
  //   new revision number is provided on input
  //   current revision number is availabled from $cur_content['rev']

  MySQLBeginTransaction();
  try {
    // modification timestamp
    $rc = MySQLExecute('update tlc_tt_surveys set modified=now() where id=?','i',$id);
    if(!$rc) {
      log_error("[$errid] Failed to update modification timestamp for id=$id");
      throw FailedToUpdate("update modification timestamp");
    }

    // survey name 
    if($name) 
    {
      $cur_name = MySQLSelectValue("select title from tlc_tt_surveys where id='$id'");
      if($name !== $cur_name) {
        $has_change = true;
        $rc = MySQLExecute('update tlc_tt_surveys set title=? where id=?','si',$name,$id);
        if($rc === false) {
          log_error("[$errid] Failed to update entry ($id,$name)");
          throw FailedToUpdate("updating name");
        }
      }
    }

    // survey options
    $cur_options = $cur_content['options'];
    $new_options = $new_content['options'];
    log_dev("update_survey:: cur_options: ".print_r($cur_options,true));
    log_dev("update_survey:: new_options: ".print_r($new_options,true));

    foreach($new_options as $option_id => $option_text) {
      if(! array_key_exists($option_id,$cur_options)) {
        $has_change = true;
        $query = <<<SQL
          INSERT into tlc_tt_survey_options 
                 (survey_id, id, survey_rev, text) 
          VALUES (?,?,?,?)
        SQL;
        log_dev("$query => ($id, $option_id, $rev, $option_text)");
        MySQLExecute($query,'iiis', $id, $option_id, $rev, $option_text);
      }
      else if($option_text !== $cur_options[$option_id]) {
        $has_change = true;
        $query = <<<SQL
          UPDATE tlc_tt_survey_options
             SET text=?
           WHERE survey_id=? AND id=? AND survey_rev=?
        SQL;
        log_dev("$query => ($option_text, $id, $option_id, $rev)");
        MySQLExecute($query,'siii',$option_text,$id,$option_id,$rev);
      }
    }

    // survey sections
    // TODO: If revision tracking and new_rev != old_rev, 
   
    $cur_sections = $cur_content['sections'];
    $new_sections = $new_content['sections'];

    // sort the sections based on the provided seq value
    ksort($new_sections);

    // predefine the insertion query.  It will be used both for new/updated section sequences
    //   and for NULLing out old sequences indices that are no longer in use.
    $query = <<<SQL
      INSERT into tlc_tt_survey_sections
             (survey_id, survey_rev, sequence, name, show_name, description, feedback)
      VALUES (?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE
             name = VALUES(name),
             show_name = VALUES(show_name),
             description = VALUES(description),
             feedback = VALUES(feedback)
    SQL;

    // couple notes on the foreach loop
    //  - we no longer care about the provided seq value, it served its purpose for sorting
    //  - we will be creating new seq indices for insertion into the database
    $seq = 0;
    foreach($new_sections as $new_data) {
      $seq += 1; // do it up front so we can do an early continue below

      $name        = null_on_empty($new_data['name']        ?? '');
      $show_me     = null_on_empty($new_data['labeled']     ?? '');  // i know... but 'show_me' caused DOM issues
      $description = null_on_empty($new_data['description'] ?? '');
      $feedback    = null_on_empty($new_data['feedback']    ?? '');

      $cur_data = $cur_sections[$seq] ?? null;
      $unchanged = (
        $cur_data
        && ( $name        === null_on_empty($cur_data['name']        ?? '' ))
        && ( $show_me     === null_on_empty($cur_data['labeled']     ?? '' ))
        && ( $description === null_on_empty($cur_data['description'] ?? '' ))
        && ( $feedback    === null_on_empty($cur_data['feedback']    ?? '' ))
      );
      if($unchanged) { continue; } // no changes found, skip to next new_section

      $has_change = true;

      MySQLExecute($query,'iiisiss',$id,$rev,$seq, $name,$show_me,$description,$feedback);

      log_dev("$query => ($id,$rev,$seq, $name,$show_me,$description,$feedback)");
    }

    // null out any section sequences that exceed our current max value
    $max_seq = MySQLSelectValue('select max(sequence) from tlc_tt_survey_sections where survey_id=?', 'i', $id);
    if($max_seq) {
      while($seq < $max_seq) {
        $seq += 1;
        MySQLExecute($query,'iiisiss',$id,$rev,$seq,null,null,null,null);
      }
    }

    todo('REMOVE ALL unused survey options');

    // We want to update the PDF file last so we don't need to undo this if there was any 
    //   sort of failure updating the other survey fields or content.

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
  }
  catch(FailedToUpdate $e)
  {
    MySQLRollback();
    $error = "Failed to update survey. Please report error $errid to a tech admin";
    return false;
  }

  if($has_change) { MySQLCommit(); }
  else            { MySQLRollback(); }

  return true;
}

