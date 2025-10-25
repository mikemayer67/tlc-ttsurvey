<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));

function get_user_responses($userid,$survey_id,$draft=null)
{
  // The responses that this function returns depends on the value of $draft:
  //      0 : returns only submitted responses
  //      1 : returns only draft responses 
  //   null : returns both submitted and draft responses
  //
  // The return value is an associated array
  //   responses : [ 'timestamp' => unix timestamp, 'responses' => response array ] 
  //   draft     : responses
  //   submitted : responses
  //   both      : [ 'draft' => responses, 'submitted => responses ]

  $query = <<<SQL
    SELECT UNIX_TIMESTAMP(draft)     as draft,
           UNIX_TIMESTAMP(submitted) as submitted
      FROM tlc_tt_user_status 
     WHERE userid=(?) AND survey_id=(?)
  SQL;

  $status = MySQLSelectRow($query,'si', $userid, $survey_id);
  if (!$status || !is_array($status)) { return []; }
  
  if(is_null($draft))
  {
    // returns both draft and submitted by recursively calling get_user_responses
    //   recursion is limited to a depth of 1 because of non-null $draft value
    return [
      'submitted' => get_user_responses($userid,$survey_id,0),
      'draft'     => get_user_responses($userid,$survey_id,1),
    ];
  }

  // returns either draft or submitted, not both

  $timestamp = $status[$draft ? 'draft' : 'submitted'] ?? null;
  if(!$timestamp ) { return []; }

  $query = <<<SQL
    SELECT question_id, selected, free_text, qualifier, other
      FROM tlc_tt_responses
     WHERE userid=(?) AND survey_id=(?) AND draft=(?);
  SQL;

  $rows = MySQLSelectRows($query, 'sii', $userid, $survey_id, $draft?1:0);

  $responses = array();
  foreach( $rows as $row ) {
    $qid = $row['question_id'];
    $responses[$qid] = $row;
  }

  $query = <<<SQL
    SELECT question_id, option_id
      FROM tlc_tt_response_options
     WHERE userid=(?) 
       AND survey_id=(?)
       AND draft=(?);
  SQL;

  $rows = MySQLSelectRows($query, 'sii', $userid, $survey_id, $draft?1:0);
  foreach($rows as $row) {
    $qid = $row['question_id'];
    if(!array_key_exists($qid,$responses)) {
      internal_error("There should be no response options for non-existent question $qid");
    }
    if(!array_key_exists('options',$responses[$qid])) {
      $responses[$qid]['options'] = array();
    }
    $responses[$qid]['options'][] = $row['option_id'];
  }

  return [
    'timestamp' => $timestamp,
    'responses' => $responses,
  ];
}


function withdraw_user_responses($userid,$survey_id)
{
  $queries = [];

  // remove all existing draft responses
  $queries[] = <<<SQL
    DELETE from tlc_tt_responses
     WHERE userid=(?)
       AND survey_id=(?)
       AND draft=1;
  SQL;

  // copy any submitted responses to draft versions
  //  (cannot simply update the status as this would break the response option foreign key)
  $queries[] = <<<SQL
    INSERT into tlc_tt_responses 
           ( userid, survey_id, question_id, draft, selected, free_text, qualifier, other)
    SELECT   userid, survey_id, question_id, 1,     selected, free_text, qualifier, other
      FROM tlc_tt_responses
     WHERE userid=(?)
       AND survey_id=(?);
  SQL;

  // relink the response options from their submitted parent to the draft parent
  $queries[] = <<<SQL
    UPDATE tlc_tt_response_options
       SET draft=1
     WHERE userid=(?)
       AND survey_id=(?);
  SQL;

  // remove the submitted responses
  $queries[] = <<<SQL
    DELETE from tlc_tt_responses
     WHERE userid=(?)
       AND survey_id=(?)
       AND draft=0;
  SQL;

  // update the user status table
  $queries[] = <<<SQL
    UPDATE tlc_tt_user_status 
       SET draft = submitted, submitted = NULL
     WHERE userid=(?)
       AND survey_id=(?);
  SQL;

  MySQLBeginTransaction();

  foreach( $queries as $query ) {
    if( false === MySQLExecute($query,'si',$userid,$survey_id) ) {
      MySQLRollback();
      return false;
    }
  }

  MySQLCommit();
  return true;
}


function restart_user_responses($userid,$survey_id)
{
  $query = <<<SQL
    DELETE from tlc_tt_user_status 
     WHERE userid=(?) 
       AND survey_id=(?)
  SQL;

  $result = MySQLExecute($query, 'si', $userid, $survey_id);
  return false !== $result;
}
