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

function survey_content($survey_id)
{
  todo('remove bogus survey content');
  $sections = [
    1 => [ 'name'=>'Welcome', 'labeled'=>0, 'feedback'=>'' ],
    2 => [ 'name'=>'Section 1', 'description'=>"some words about section 1", 'labeled'=>1, 'feedback'=>'' ],
    3 => [ 'name'=>'Section Deux', 'description'=>"But why is the name partially in Frenche?  That's a might fine question for which I do not have an answer.  Ok, reasonable,  .... but what are we even talking about at this point?", 'labeled'=>1, 'feedback'=>'So, what iss up?' ],
    4 => [ 'name'=>'Section 3', 'description'=>"some words about section 3", 'labeled'=>1, 'feedback'=>'' ],
    5 => [ 'name'=>'Section 4', 'description'=>"some words about section 4", 'labeled'=>1, 'feedback'=>'' ],
    7 => [ 'name'=>'Section 5', 'description'=>"some words about section 5", 'labeled'=>1, 'feedback'=>'' ],
    9 => [ 'name'=>'Section 6', 'description'=>"some words about section 6", 'labeled'=>1, 'feedback'=>'' ],
    8 => [ 'name'=>'Section 7', 'description'=>"some words about section 7", 'labeled'=>1, 'feedback'=>'' ],
  ];
  $options = [
    1 => 'neative i^2',
    2 => 'Two',
    3 => 'Three',
    4 => 'Whatever',
  ];

  $id = 0;
  $questions = array();
  for($i=1; $i<=count($sections); ++$i)
  {
    $s = $i > 5 ? 1+$i : $i;

    $questions[++$id] = [
      'section' => $s,
      'sequence' => 1 + ($id -1)%10,
      'type' => 'INFO', 
      'wording' => 'Info Text', 
      'info'=>'This is where the text goes.  Skipping markdown/HTML for now (**mostly**).  But am adding a some italics and *bold*.',
    ];
    $questions[++$id] = [
      'section' => $s,
      'sequence' => 1 + ($id -1)%10,
      'type' => 'BOOL', 
      'wording' => 'Yes/No Questions',
      'qualifier' => 'Why or why not?',
      'description' => 'Blah blah blah... This is important because',
      'info'=>'This is popup info.  Just here to see if popups are working',
    ];
    $questions[++$id] = [
      'section' => $s,
      'sequence' => 1 + ($id -1)%10,
      'type' => 'SELECT_ONE', 
      'wording' => 'Select Question #1',
      'other' => 'Other',
      'qualifier' => 'Anything we should know?',
      'description' => "Pick whichever answer best applies.  Or provide your own if you don't like the options provided",
      'info'=>'This is popup info.  Just here to see if popups are working',
      'options' => [ [3, false], [2, false], [1,false], ],
    ];
    $questions[++$id] = [
      'section' => $s,
      'sequence' => 1 + ($id -1)%10,
      'type' => 'SELECT_ONE', 
      'wording' => 'Select Question #2',
      'qualifier' => 'Anything we should know?',
      'description' => 'Pick whichever answer best applies.',
      'info'=>'This is popup info.  Just here to see if popups are working',
      'options' => [ [3, false], [2, false], [1,true], ],
    ];
    $questions[++$id] = [
      'section' => $s,
      'sequence' => 1 + ($id -1)%10,
      'type' => 'SELECT_MULTI', 
      'wording' => 'Multi Select #1',
      'other' => 'Other',
      'qualifier' => 'Anything we should know?',
      'description' => 'Pick whichever answer or answers best apply.  Provide your own if you think we missed something.',
      'info'=>'This is popup info.  Just here to see if popups are working',
      'options' => [ [1, false], [2, false], [3,true], [4,true] ],
    ];
    $questions[++$id] = [
      'section' => $s,
      'sequence' => 1 + ($id -1)%10,
      'type' => 'SELECT_MULTI', 
      'wording' => 'Multi Select #2',
      'qualifier' => 'Anything we should know?',
      'description' => 'Pick whichever answer or answers best apply.',
      'info'=>'This is popup info.  Just here to see if popups are working',
      'options' => [ [1, false], [2, false], [3,true], [4,true] ],
    ];
    $questions[++$id] = [
      'section' => $s,
      'sequence' => 1 + ($id -1)%10,
      'type' => 'FREETEXT', 
      'wording' => 'Your thoughts?',
      'description' => 'What else would you like us to know?',
      'info'=>'This is popup info.  Just here to see if popups are working',
    ];
  }
  return [
    'sections'  => $sections,
    'options'   => $options,
    'questions' => $questions,
    'next_ids'  => ['survey'=>100, 'question'=>200, 'option'=>50],
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

