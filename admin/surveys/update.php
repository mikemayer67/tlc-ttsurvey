<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }
require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/strings.php'));
require_once(app_file('include/surveys.php'));

class FailedToUpdate extends \Exception {}

function update_survey($survey_id, $survey_rev, $content, $details)
{
  // We want the update to be all or nothing, so wrap it in a MySQL transaction
  //   so that we can do a rollback if something goes wrong
  MySQLBeginTransaction();

  try {
    // begin by purging all data for current survey_id and rev
    //   should only need to remove the survey id/rev from the survey revision table
    //   the foreign keys should cascade the deleted to all of the other tables
    //
    // but first, retrieve the current title, if needed
    $details['title_sid'] = MySQLSelectValue(
      "select title_sid from tlc_tt_survey_revisions where survey_id=$survey_id and survey_rev=$survey_rev"
    );

    MySQLExecute("delete from tlc_tt_survey_revisions where survey_id=$survey_id and survey_rev>=$survey_rev");

    // now we can start repopulating the current revision

    update_survey_revision  ($survey_id,$survey_rev,$details);
    update_survey_options   ($survey_id,$survey_rev,$content);
    update_survey_content   ($survey_id,$survey_rev,$content);

    // save updating the pdf until last as this cannot be rolled back as easily as a MySQL transaction
    update_survey_pdf       ($survey_id,$survey_rev,$details);

    // final step is to commit the transaction
    //   if there was an exception the transaction will be rolled back in the catch block
    MySQLCommit(); 
  }
  catch(Exception $e)
  {
    MySQLRollback();
    throw $e;
  }
}

function update_survey_revision($survey_id,$survey_rev,$details)
{
  $title_sid = strings_find_or_create($details['title'] ?? null) ??  $details['title_sid'] ??  null;
  if(!$title_sid) {
    throw new FailedtoUpdate("Cannot resolve survey title for survey $survey_id rev $survey_rev");
  }

  $update = <<<SQL
    INSERT into tlc_tt_survey_revisions 
           (survey_id,survey_rev,title_sid)
    VALUES ($survey_id, $survey_rev,$title_sid)
  SQL;

  if( MySQLExecute($update) === false ) {
    throw new FailedToUpdate("Failed to update title for survey $survey_id rev $survey_rev"); 
  }
}

function update_survey_pdf($survey_id,$survey_rev,$details)
{
  todo("test update_survey_pdf when browser UX is suffiently mature to upload pdfs");

  $action = $details['pdf_action'] ?? null;
  if(!$action) { return; }

  $pdf_path = Surveys::expected_pdf_path($survey_id);

  if($action === 'drop' || $action === 'replace') {
    if(file_exists($pdf_path)) {
      if(!unlink($pdf_path)) {
        throw new FailedToUpdate("Failed to drop existing PDF file: $pdf_path");
      }
    }
  }

  if($action === 'add' || $action === 'replace') {
    $new_pdf = $details['new_pdf'] ?? null;

    if(!$new_pdf) {
      throw new FailedToUpdate("Failed to update new PDF file, no file specified");
    }
    if(!move_uploaded_file($new_pdf,$pdf_path)) {
      throw new FailedToUpdate("Failed to upload new PDF file: $new_pedf to: $pdf_path");
    }
  }

  return true;
}

function update_survey_options($survey_id,$survey_rev,$content)
{
  $options = $content['options'];

  $insert = <<<SQL
    INSERT into tlc_tt_survey_options (survey_id, survey_rev, option_id, text_sid) 
    VALUES ($survey_id,$survey_rev,?,?)
    ON DUPLICATE KEY UPDATE text_sid = values(text_sid)
  SQL;

  foreach($options as $option_id => $text_str) 
  {
    $text_sid = strings_find_or_create($text_str);
    if( MySQLExecute($insert,'ii', $option_id, $text_sid) === false) {
      throw new FailedToUpdate("Failed to update survey options ($option_id, $text_str)");
    }
  }
}

function update_survey_content($survey_id,$survey_rev,$content)
{

  // conolidate questions into the correponding sections
  $sections = consolidate_survey_content($content);

  $insert = <<<SQL
    INSERT into tlc_tt_survey_sections
           (survey_id, survey_rev, sequence, name_sid, collapsible, description_sid, feedback_sid)
    VALUES ($survey_id, $survey_rev,?,?,?,?,?)
  SQL;

  $sequence = 1;
  foreach( $sections as $section ) {
    $rc = MySQLExecute(
      $insert, 'iiiii',
      $sequence,
      strings_find_or_create($section['name']),
      ($section['collapsible'] ?? null) ? 1 : 0,
      strings_find_or_create($section['description']),
      strings_find_or_create($section['feedback'])
    );
    if($rc === false) {
      $sequence = $section['sequence'];
      throw new FailedToUpdate("Failed to update survey sections ($sequence)");
    }

    if(array_key_exists('questions',$section)) {
      update_survey_questions($survey_id,$survey_rev,$sequence,$section['questions']);
    }

    $sequence += 1;
  }
}

function update_survey_questions($survey_id,$survey_rev,$section_seq,$questions)
{
  usort($questions, fn($a,$b) => $a['sequence'] <=> $b['sequence']);

  $insert = <<<SQL
    INSERT into tlc_tt_survey_questions
           (question_id, survey_id, survey_rev,
            wording_sid,question_type,multiple,
            other_sid,qualifier_sid,description_sid,info_sid)
    VALUES (?,$survey_id,$survey_rev,?,?,?,?,?,?,?)
  SQL;

  $sequence = 1;
  foreach($questions as $question) {
    $question_id = $question['id'];

    $type        = $question['type'];
    $wording     = $question['wording'] ?? $question['infotag'] ?? null;
    $other       = $question['other'] ?? null;
    $qualifier   = $question['qualifier'] ?? null;
    $description = $question['description'] ?? null;
    $info        = $question['info'] ?? $question['popup'] ?? null;

    if(str_starts_with($type,'SELECT')) {
      $multiple = str_ends_with($type,'MULTI') ? 1 : 0;
      $type = 'OPTIONS';
    } else {
      $multiple = null;
    }

    $rc = MySQLExecute(
      $insert, 'iisiiiii',
      $question_id,
      strings_find_or_create($wording),
      $type, $multiple,
      strings_find_or_create($other),
      strings_find_or_create($qualifier),
      strings_find_or_create($description),
      strings_find_or_create($info)
    );
    if($rc === false) {
      throw new FailedToUpdate("Failed to update survey question $question_id");
    }

    update_question_map($survey_id,$survey_rev,$question_id,$section_seq,$sequence);

    if(array_key_exists('options',$question)) {
      update_question_options($survey_id,$survey_rev,$question_id,$question['options']);
    }

    $sequence += 1;
  }
}

function update_question_map($survey_id,$survey_rev,$question_id,$section_seq,$question_seq)
{
  $insert = <<<SQL
    INSERT into tlc_tt_question_map
           (survey_id,survey_rev,section_seq,question_seq,question_id)
    VALUES ($survey_id,$survey_rev,$section_seq,$question_seq,$question_id)
  SQL;

  $rc = MySQLExecute($insert);

  if($rc === false) {
    throw new FailedToUpdate("Failed to update question_map $question_id");
  }
}

function update_question_options($survey_id,$survey_rev,$question_id,$options)
{
  $insert = <<<SQL
    INSERT into tlc_tt_question_options
           (survey_id,survey_rev,question_id,secondary,sequence,option_id)
    VALUES ($survey_id,$survey_rev,$question_id,?,?,?)
  SQL;

  $primary = [];
  $secondary = [];

  foreach($options as $option) {
    if($option[1]) { $secondary[] = $option[0]; }
    else           { $primary[]   = $option[0]; }
  }

  foreach([$primary,$secondary] as $key=>$group) {
    $sequence = 1;
    foreach($group as $option_id) {
      $rc = MySQLExecute($insert,'iii', $key, $sequence, $option_id);
      if($rc === false) {
        throw new FailedToUpdate("Failed to update question options $question_id/$option_id");
      }
      $sequence += 1;
    }
  }
}

function consolidate_survey_content($content)
{
  $sections = [];
  foreach($content['sections'] as $id=>$section) {
    $section['sequence'] = $id;
    $sections[$id] = $section;
  }

  foreach($content['questions'] as $question) {
    $section_id = $question['section'];
    if(isset($sections[$section_id])) {
      $sections[$section_id]['questions'][] = $question;
    }
  }

  foreach(array_keys($sections) as $section_id) {
    if(isset($sections[$section_id]['questions'])) {
      usort($sections[$section_id]['questions'], fn($a,$b) => $a['sequence'] <=> $b['sequence']);
    }
  }

  return $sections;
}
