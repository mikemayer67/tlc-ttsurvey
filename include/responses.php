<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));

function get_user_responses($userid,$survey_id,$draft=null)
{
  // The responses that this function returns depends on the value of $draft:
  //      0 : returns only filed responses (submitted)  
  //      1 : returns only draft responses 
  //   null : returns both filed and draft responses
  //
  // The return value is an associated array
  //   responses : [ 'timestamp' => unix timestamp, 'responses' => response array ] 
  //   draft     : responses
  //   filed     : responses
  //   both      : [ 'draft' => responses, 'filed => responses ]

  $query = <<<SQL
    SELECT UNIX_TIMESTAMP(draft)     as draft,
           UNIX_TIMESTAMP(submitted) as filed
      FROM tlc_tt_user_status 
     WHERE userid=(?) AND survey_id=(?)
  SQL;

  $status = MySQLSelectRow($query,'si', $userid, $survey_id);
  if (!$status || !is_array($status)) { return []; }
  
  if(is_null($draft))
  {
    // returns both draft and filed by recursively calling get_user_responses
    //   recursion is limited to a depth of 1 because of non-null $draft value
    return [
      'filed' => get_user_responses($userid,$survey_id,0),
      'draft' => get_user_responses($userid,$survey_id,1),
    ];
  }

  // returns either draft or filed, not both

  $timestamp = $status[$draft ? 'draft' : 'filed'] ?? null;
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
