<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }
require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/strings.php'));
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
    $update = "UPDATE tlc_tt_surveys SET active=NULL, closed=NULL WHERE survey_id=?";
    break;
  case 'active':
    $update = "UPDATE tlc_tt_surveys SET active=CURRENT_TIMESTAMP, closed=NULL WHERE survey_id=?";
    break;
  case 'closed':
    $update = "UPDATE tlc_tt_surveys SET closed=CURRENT_TIMESTAMP WHERE survey_id=?";
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
    FROM tlc_tt_surveys
    WHERE survey_id=?
  SQL;
  return MySQLSelectValue($query,'i',$survey_id);
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
    
    $details = MySQLSelectRow("select * from tlc_tt_surveys where survey_id=$survey_id");
    if($title) {
      $details['title_sid'] = strings_find_or_create($title);
    }

    MySQLExecute("delete from tlc_tt_surveys where survey_id=$survey_id");

    // now we can start repopulating the current revision

    update_survey_details($survey_id,$details);
    update_survey_options($survey_id,$content);
    update_survey_content($survey_id,$content);

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

function update_survey_details($survey_id,$details)
{
  $parent_id = $details['parent_id'];
  $title_sid = $details['title_sid'];
  $created   = $details['created'];
  // modified gets set via the default
  $active    = $details['active'];
  $closed    = $details['closed'];

  $update = <<<SQL
    INSERT into tlc_tt_surveys
           (survey_id,parent_id,title_sid,created,active,closed)
    VALUES ($survey_id,?,?,?,?,?)
  SQL;

  $rc = MySQLExecute($update,'iisss', $parent_id, $title_sid, $created, $active, $closed);

  if( $rc === false ) {
    throw new FailedToUpdate("Failed to update title for survey $survey_id"); 
  }
}

function update_survey_options($survey_id,$content)
{
  $options = $content['options'];

  $insert = <<<SQL
    INSERT into tlc_tt_survey_options (survey_id, option_id, text_sid) 
    VALUES ($survey_id,?,?)
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

function update_survey_content($survey_id,$content)
{

  // conolidate questions into the correponding sections
  $sections = consolidate_survey_content($content);

  $insert = <<<SQL
    INSERT into tlc_tt_survey_sections
           (survey_id, sequence, name_sid, collapsible, intro_sid, feedback_sid)
    VALUES ($survey_id,?,?,?,?,?)
  SQL;

  $sequence = 1;
  foreach( $sections as $section ) {
    $rc = MySQLExecute(
      $insert, 'iiiii',
      $sequence,
      strings_find_or_create($section['name']),
      ($section['collapsible'] ?? null) ? 1 : 0,
      strings_find_or_create($section['intro']),
      strings_find_or_create($section['feedback'])
    );
    if($rc === false) {
      $sequence = $section['sequence'];
      throw new FailedToUpdate("Failed to update survey sections ($sequence)");
    }

    if(array_key_exists('questions',$section)) {
      update_survey_questions($survey_id,$sequence,$section['questions']);
    }

    $sequence += 1;
  }
}

function update_survey_questions($survey_id,$section_seq,$questions)
{
  usort($questions, fn($a,$b) => $a['sequence'] <=> $b['sequence']);

  $insert = <<<SQL
    INSERT into tlc_tt_survey_questions
           (question_id, survey_id,
            wording_sid,question_type,question_flags,
            other_sid,qualifier_sid,intro_sid,info_sid)
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

    $rc = MySQLExecute(
      $insert, 'iisiiiii',
      $question_id,
      strings_find_or_create($wording),
      $type, $flags->get_bits(),
      strings_find_or_create($other),
      strings_find_or_create($qualifier),
      strings_find_or_create($intro),
      strings_find_or_create($info)
    );
    if($rc === false) {
      throw new FailedToUpdate("Failed to update survey question $question_id");
    }

    update_question_map($survey_id,$question_id,$section_seq,$sequence);

    if(array_key_exists('options',$question)) {
      update_question_options($survey_id,$question_id,$question['options']);
    }

    $sequence += 1;
  }
}

function update_question_map($survey_id,$question_id,$section_seq,$question_seq)
{
  $insert = <<<SQL
    INSERT into tlc_tt_question_map
           (survey_id,section_seq,question_seq,question_id)
    VALUES ($survey_id,$section_seq,$question_seq,$question_id)
  SQL;

  $rc = MySQLExecute($insert);

  if($rc === false) {
    throw new FailedToUpdate("Failed to update question_map $question_id");
  }
}

function update_question_options($survey_id,$question_id,$options)
{
  $insert = <<<SQL
    INSERT into tlc_tt_question_options
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
