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
    SELECT UNIX_TIMESTAMP(draft)      as draft,
           UNIX_TIMESTAMP(submitted)  as submitted
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

    $selected = $row['selected'];
    $row['selected'] = $selected !== null ? [$selected] : [];
    
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
    $responses[$qid]['selected'][] = $row['option_id'];
  }

  $query = <<<SQL
    SELECT section_id, feedback
      FROM tlc_tt_section_feedback
     WHERE userid=(?)
       AND survey_id=(?)
       AND draft=(?);
  SQL;

  $rows = MySQLSelectRows($query,'sii',$userid, $survey_id, $draft?1:0);

  $feedback = array();
  foreach($rows as $row) {
    $feedback[$row['section_id']] = $row['feedback'];
  }

  return [
    'timestamp' => $timestamp,
    'responses' => $responses,
    'feedback'  => $feedback,
  ];
}

function get_all_responses($survey_id)
{
  $query = <<<SQL
    SELECT question_id, userid, selected, free_text, qualifier, other
      FROM tlc_tt_responses
     WHERE draft=0 and survey_id=?;
  SQL;

  $rows = MySQLSelectRows($query,'i', $survey_id);

  $questions = [];
  foreach($rows as $row) {
    $qid    = $row['question_id'];
    $userid = $row['userid'];
    $questions[$qid][$userid] = $row;
  }

  $query = <<<SQL
    SELECT ro.question_id, ro. userid,ro.option_id
      FROM tlc_tt_response_options ro
      LEFT JOIN tlc_tt_survey_options so on so.survey_id=ro.survey_id and so.option_id=ro.option_id
      WHERE ro.draft=0 and ro.survey_id=?;
  SQL;

  $rows = MySQLSelectRows($query,'i', $survey_id);
  foreach($rows as $row) {
    $qid    = $row['question_id'];
    $userid = $row['userid'];
    $oid    = $row['option_id'];
    $questions[$qid][$userid]['options'][] = $oid;
  }

  $query = <<<SQL
    SELECT section_id, userid, feedback
      FROM tlc_tt_section_feedback
     WHERE draft=0 and survey_id=?;
  SQL;

  $rows = MySQLSelectRows($query,'i', $survey_id);

  $sections = [];
  foreach($rows as $row) {
    $sid    = $row['section_id'];
    $userid = $row['userid'];
    $sections[$sid][$userid] = $row['feedback'];
  }

  return ['questions'=>$questions, 'sections'=>$sections];
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
       SET draft = submitted, submitted=NULL, email_sent=NULL, sent_to=NULL
     WHERE userid=(?)
       AND survey_id=(?);
  SQL;
  
  // proceed to executing the queries in a single transaction

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


function drop_user_draft_responses($userid,$survey_id)
{
  $queries = [];

  // remove all existing draft responses
  $queries[] = <<<SQL
    DELETE from tlc_tt_responses
     WHERE userid=(?)
       AND survey_id=(?)
       AND draft=1;
  SQL;

  // update the user status table
  $queries[] = <<<SQL
    UPDATE tlc_tt_user_status 
       SET draft = NULL
     WHERE userid=(?)
       AND survey_id=(?);
  SQL;

  // proceed to executing the queries in a single transaction

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


function confirmation_email_sent($userid,$survey_id,$email=null)
{
  // note this function is both getter and setter depending on if $email is provided
  
  if($email) {
    // setter
    $query = <<<SQL
      UPDATE tlc_tt_user_status
         SET email_sent = CURRENT_TIMESTAMP, sent_to=(?)
       WHERE userid=(?)
         AND survey_id=(?) 
    SQL;
    return MySQLExecute($query,'ssi',$email,$userid,$survey_id);
  }
  else {
    // getter
    $query = <<<SQL
    SELECT UNIX_TIMESTAMP(email_sent) as timestamp,
           sent_to                    as address
      FROM tlc_tt_user_status 
     WHERE userid=(?) AND survey_id=(?)
    SQL;

    $row = MySQLSelectRow($query,'si', $userid, $survey_id);

    if(!$row || empty($row['timestamp'])) {
      return []; 
    }
    return $row;
  }
}


function update_user_responses($userid,$survey_id,$action,$responses)
{
  // handle the action specific setup

  unset($_SESSION['ui-cache-id']);

  switch($action) 
  {
    case 'delete':
      drop_user_draft_responses($userid,$survey_id);
      return;

    case 'save':
      $_SESSION['ui-cache-id'] = $_POST['ui-cache-id'];
      $draft = 1;
      break;

    case 'submit':
      $draft = 0;
      break;

    default:
      internal_error("Invalid update_user_responses action ($action)");
      break;
  }

  // wrap all database updates in a transaction to allow for rollback on failure
  MySQLBeginTransaction();

  // remove the existing reponses
  // if saving a draft, only remove the draft responses
  // if submitting, remove all responses 
  $action_clause = $draft ? 'AND draft=1' : '';
  $query = <<<SQL
    DELETE from tlc_tt_responses 
     WHERE userid=(?) AND survey_id=(?)
     $action_clause;
  SQL;
  if(!_update_user_response($query,'si', $userid, $survey_id) ) {  return false; }

  $query = <<<SQL
    DELETE from tlc_tt_section_feedback
     WHERE userid=(?) AND survey_id=(?)
     $action_clause;
  SQL;
  if(!_update_user_response($query,'si', $userid, $survey_id) ) {  return false; }

  // update the user status table
  if($draft) {
    $insert_list = '(userid,survey_id,draft)';
    $update_list = 'draft=CURRENT_TIMESTAMP';
  } else {
    $insert_list = '(userid,survey_id,submitted)';
    $update_list = 'draft=NULL, submitted=CURRENT_TIMESTAMP, email_sent=NULL, sent_to=NULL';
  }
  $query = <<<SQL
    INSERT into tlc_tt_user_status $insert_list
    VALUES (?,?,CURRENT_TIMESTAMP)
    ON DUPLICATE KEY UPDATE $update_list;
  SQL;
  if(!_update_user_response($query,'si', $userid, $survey_id) ) { return false; }
  
  foreach( $responses as $k=>$v )
  {
    // skip any empty input responses
    if( $v==='' ) { continue; }

    // Freetext questions
    if(preg_match('/^question-freetext-(\d+)$/',$k,$m)) {
      $query = <<<SQL
         INSERT into tlc_tt_responses (userid,survey_id,question_id,draft,free_text)
         VALUES     (?,?,?,$draft,?)
         ON DUPLICATE KEY UPDATE free_text=?;
      SQL;
      if(!_update_user_response($query,'siiss', $userid, $survey_id, $m[1], $v, $v) ) { return false; }
    }

    // Boolean questions
    elseif(preg_match('/^question-bool-(\d+)$/',$k,$m)) {
      $query = <<<SQL
         INSERT into tlc_tt_responses (userid,survey_id,question_id,draft,selected)
         VALUES     (?,?,?,$draft,1)
         ON DUPLICATE KEY UPDATE selected=1;
      SQL;
      if(!_update_user_response($query,'sii', $userid, $survey_id, $m[1]) ) { return false; }
    }

    // Single and multi select questions
    elseif(preg_match('/^question-select-(\d+)$/',$k,$m)) {
      $query = <<<SQL
         INSERT into tlc_tt_responses (userid,survey_id,question_id,draft,selected)
         VALUES     (?,?,?,$draft,?)
         ON DUPLICATE KEY UPDATE selected=?;
      SQL;
      if(!_update_user_response($query,'siiii', $userid, $survey_id, $m[1],$v,$v) ) { return false; }
    }
    elseif(preg_match('/^question-multi-(\d+)-(\d+)$/',$k,$m)) {
      // need an entry in both the responses and the response options tables
      $query = <<<SQL
         INSERT IGNORE into tlc_tt_responses (userid,survey_id,question_id,draft)
         VALUES     (?,?,?,$draft);
      SQL;
      if(!_update_user_response($query,'sii', $userid, $survey_id, $m[1]) ) { return false; }

      $query = <<<SQL
         INSERT into tlc_tt_response_options (userid,survey_id,question_id,draft,option_id)
         VALUES     (?,?,?,$draft,?);
      SQL;
      if(!_update_user_response($query,'siii', $userid, $survey_id, $m[1],$m[2]) ) { return false; }
    }
    elseif(preg_match('/^question-(?:multi|select)-(\d+)-has-other$/',$k,$m)) {
      $query = <<<SQL
         INSERT into tlc_tt_responses (userid,survey_id,question_id,draft,selected)
         VALUES     (?,?,?,$draft,0)
         ON DUPLICATE KEY UPDATE selected=0;
      SQL;
      if(!_update_user_response($query,'sii', $userid, $survey_id, $m[1]) ) { return false; }
    }
    elseif(preg_match('/^question-(?:multi|select)-(\d+)-other/',$k,$m)) {
      $query = <<<SQL
         INSERT into tlc_tt_responses (userid,survey_id,question_id,draft,other)
         VALUES     (?,?,?,$draft,?)
         ON DUPLICATE KEY UPDATE other=?;
      SQL;
      if(!_update_user_response($query,'siiss', $userid, $survey_id, $m[1],$v, $v) ) { return false; }
    }

    // Add qualifiers
    elseif(preg_match('/^question-qualifier-(\d+)$/',$k,$m)) {
      $query = <<<SQL
         INSERT into tlc_tt_responses (userid,survey_id,question_id,draft,qualifier)
         VALUES     (?,?,?,$draft,?)
         ON DUPLICATE KEY UPDATE qualifier=?;
      SQL;
      if(!_update_user_response($query,'siiss', $userid, $survey_id, $m[1],$v,$v)) { return false; }
    }

    // Section feedback
    elseif(preg_match('/^section-feedback-(\d+)$/',$k,$m)) {
      $query = <<<SQL
         INSERT into tlc_tt_section_feedback (userid,survey_id,section_id,draft,feedback)
         VALUES     (?,?,?,$draft,?);
      SQL;
      if(!_update_user_response($query,'siis', $userid, $survey_id, $m[1],$v)) { return false; }
    }
  }

  // Done... commit the changes
  MySQLCommit();
  return true;
}

function _update_user_response($query, $types, ...$params)
{
  if( false === MySQLExecute($query,$types,...$params) ) {
    MySQLRollback();
    return false;
  }
  return true;
}
