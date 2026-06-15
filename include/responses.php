<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

use mysqli_sql_exception;

require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));

/**
 * Get all of the specified user's survey responsees
 * @param string $userid 
 * @param int $survey_id 
 * @param 'draft'|'submitted' $scope 
 * @return array{ timestamp:int, responses:array } where timestap is a Unix time
 */
function get_user_responses(string $userid,int $survey_id,string $scope) : array
{
  $draft = $scope==='draft' ? 1 : 0;

  $query = <<<SQL
    SELECT UNIX_TIMESTAMP(draft)      as draft,
           UNIX_TIMESTAMP(submitted)  as submitted
      FROM tlc_srv_user_status 
     WHERE userid=? AND survey_id=?
  SQL;

  $status = MySQLFetchOneAssoc($query,'si', $userid, $survey_id);
  if (!$status) { return []; }

  // returns either draft or submitted
  $timestamp = $status[$scope] ?? null;
  if(!$timestamp ) { return []; }

  $query = <<<SQL
    SELECT question_id, selected, free_text, qualifier, other
      FROM tlc_srv_responses
     WHERE userid=? AND survey_id=? AND draft=?;
  SQL;
  $rows = MySQLFetchAllAssoc($query, 'sii', $userid, $survey_id, $draft);

  $responses = array();
  foreach( $rows as $row ) {
    $qid      = $row['question_id'];
    $selected = $row['selected'];
    $row['selected'] = $selected !== null ? [$selected] : [];
    $responses[$qid] = $row;
  }

  $query = <<<SQL
    SELECT question_id, option_id
      FROM tlc_srv_response_options
     WHERE userid=? AND survey_id=? AND draft=?;
  SQL;
  $rows = MySQLFetchAllAssoc($query, 'sii', $userid, $survey_id, $draft);

  foreach($rows as $row) {
    $qid = $row['question_id'];
    if(!array_key_exists($qid,$responses)) {
      internal_error("There should be no response options for non-existent question $qid");
    }
    $responses[$qid]['selected'][] = $row['option_id'];
  }

  return [
    'timestamp' => $timestamp,
    'responses' => $responses,
  ];
}

/**
 * Get all of the specified user's draft survey responsees
 * @param string $userid 
 * @param int $survey_id 
 * @return array{ timestamp:int, responses:array } where timestamp is a Unix time
 */
function get_user_draft_responses(string $userid,int $survey_id) : array
{
  return get_user_responses($userid, $survey_id, 'draft');
}

/**
 * Get all of the specified user's submitted survey responsees
 * @param string $userid 
 * @param int $survey_id 
 * @return array{ timestamp:int, responses:array } where timestamp is a Unix time
 */
function get_user_submitted_responses(string $userid,int $survey_id) : array
{
  return get_user_responses($userid, $survey_id, 'submitted');
}

/**
 * Get all of the specified user's survey responsees (both draft and submitted)
 * @param string $userid 
 * @param int $survey_id 
 * @return array{
 *         draft:     array{ timestamp:int, responses:array },
 *         submitted: array{ timestamp:int, responses:array }
 * } where timestamp is a Unix time
 */
function get_all_user_responses(string $userid,int $survey_id) : array
{
  return [
    'draft'     => get_user_draft_responses($userid, $survey_id),
    'submitted' => get_user_submitted_responses($userid, $survey_id)
  ];
}



/**
 * Gets all submitted responses for the specified survey
 * @param int $survey_id 
 * @return array indexed by question_id, then userid
 */
function get_all_responses(int $survey_id) : array
{
  $query = <<<SQL
    SELECT question_id, userid, selected, free_text, qualifier, other
      FROM tlc_srv_responses
     WHERE draft=0 and survey_id=?;
  SQL;
  $rows = MySQLFetchAllAssoc($query,'i', $survey_id);

  $responses = [];
  foreach($rows as $row) {
    $qid    = $row['question_id'];
    $userid = $row['userid'];
    $responses[$qid][$userid] = $row;
  }

  $query = <<<SQL
    SELECT ro.question_id, ro. userid,ro.option_id
      FROM tlc_srv_response_options ro
      LEFT JOIN tlc_srv_survey_options so on so.survey_id=ro.survey_id and so.option_id=ro.option_id
      WHERE ro.draft=0 and ro.survey_id=?;
  SQL;
  $rows = MySQLFetchAllAssoc($query,'i', $survey_id);
  
  foreach($rows as $row) {
    $qid    = $row['question_id'];
    $userid = $row['userid'];
    $oid    = $row['option_id'];
    $responses[$qid][$userid]['options'][] = $oid;
  }

  return $responses;
}


/**
 * Moves all of a user's submitted responses back to draft state.
 * 
 * @param string $userid 
 * @param int $survey_id 
 * @return bool true=success, false=no changes made in database
 * 
 * @note This function wraps all changes in a transaction so that if any one 
 *       of them fails, all changes are rolled back as if no changes were attempted.
 */
function withdraw_user_responses(string $userid,int $survey_id) : bool
{
  // Build the exception handler in case any of the prepared statements fail
  $exception_handler = function(mysqli_sql_exception $e) use ($userid) {
    log_warning("Failed to withdraw responses from $userid: " . $e->getMessage());
    MySQLRollback();
  };

  // Build a list of all the SQL commands to withdraw the user's responses
  $statements = [
    // remove all existing draft responses
    new MySQLPreparedExec(
      'DELETE from tlc_srv_responses WHERE userid=? AND survey_id=? AND draft=1',
      'si', $exception_handler
    ),
    // copy any submitted responses to draft versions
    //  (cannot simply update the status as this would break the response option foreign key)
    new MySQLPreparedExec( 
      <<<SQL
        INSERT into tlc_srv_responses 
              ( userid, survey_id, question_id, draft, selected, free_text, qualifier, other)
        SELECT   userid, survey_id, question_id, 1,     selected, free_text, qualifier, other
          FROM tlc_srv_responses
        WHERE userid=? AND survey_id=?
      SQL, 
      'si', $exception_handler
    ),
    // relink the response options from their submitted parent to the draft parent
    new MySQLPreparedExec(
      'UPDATE tlc_srv_response_options SET draft=1 WHERE userid=? AND survey_id=?',
      'si', $exception_handler
    ),
    // remove the submitted responses
    new MySQLPreparedExec(
      'DELETE from tlc_srv_responses WHERE userid=? AND survey_id=? AND draft=0',
      'si', $exception_handler
    ),
    // update the user status table
    new MySQLPreparedExec(
      <<<SQL
        UPDATE tlc_srv_user_status 
          SET draft = submitted, submitted=NULL, email_sent=NULL, sent_to=NULL
        WHERE userid=? AND survey_id=?;
      SQL,
      'si', $exception_handler
    ),
  ];

  // And now loop over the SQL statements and execute them.
  
  MySQLBeginTransaction();

  $success = true;
  foreach ($statements as $stmt) {
    if($stmt->run($userid,$survey_id) === null) {
      $success = false;
      break;
    }
  }

  // on failure, rollback will occur in the exception handler
  // on sucess, we can now commit the changes
  if($success) { MySQLCommit(); }

  return $success;
}


/**
 * Drops all of a user's draft responses
 * 
 * @param string $userid 
 * @param int $survey_id 
 * @return bool true=success, false=no changes made in database
 * 
 * @note This function wraps all changes in a transaction so that if any one 
 *       of them fails, all changes are rolled back as if no changes were attempted.
 */
function drop_user_draft_responses(string $userid,int $survey_id) : bool
{
  // Build the exception handler in case any of the prepared statements fail
  $exception_handler = function(mysqli_sql_exception $e) use ($userid) {
    log_warning("Failed to withdraw responses from $userid: " . $e->getMessage());
    MySQLRollback();
  };

  // Build a list of all the SQL commands to withdraw the user's responses
  $statements = [
    // remove all existing draft responses
    new MySQLPreparedExec(
      'DELETE from tlc_srv_responses WHERE userid=? AND survey_id=? AND draft=1',
      'si', $exception_handler
    ),
    // update the user status table
    new MySQLPreparedExec(
      'UPDATE tlc_srv_user_status SET draft = NULL WHERE userid=? AND survey_id=?',
      'si', $exception_handler
    ),
  ];

  // And now loop over the SQL statements and execute them.
  
  MySQLBeginTransaction();

  $success = true;
  foreach ($statements as $stmt) {
    if($stmt->run($userid,$survey_id) === null) {
      $success = false;
      break;
    }
  }

  // on failure, rollback will occur in the exception handler
  // on sucess, we can now commit the changes
  if($success) { MySQLCommit(); }

  return $success;
}


/**
 * Drops all of a user's draft and submitted responses
 * @param string $userid 
 * @param int $survey_id 
 * @return bool true=success, false=no changes made in database
 */
function restart_user_responses(string $userid,int $survey_id) : bool
{
  return MySQLExecute(
    'DELETE from tlc_srv_user_status WHERE userid=?  AND survey_id=?','si',
    fn($e) => log_warning("Failed to drop all responses for $userid: " . $e->getMessage())
  ) !== null;
}


/**
 * Setter for the timestamp for the last confirmation email sent
 * @param string $userid 
 * @param int $survey_id 
 * @param string $email address
 * @return void
 */
function set_confirmation_email_timestamp(string $userid,int $survey_id,string $email) : void
{
  $query = <<<SQL
    UPDATE tlc_srv_user_status
       SET email_sent = CURRENT_TIMESTAMP, sent_to=?
     WHERE userid=?  AND survey_id=? 
  SQL;
  MySQLExecute($query,'ssi',$email,$userid,$survey_id);
}

/**
 * Getter for the timestamp for the last confirmation email sent
 * @param string $userid 
 * @param int $survey_id 
 * @return array{timestamp:int, address:string}|array{}
 */
function get_confirmation_email_timestamp(string $userid,int $survey_id) : array
{
  $query = <<<SQL
    SELECT UNIX_TIMESTAMP(email_sent) as timestamp,
           sent_to                    as address
      FROM tlc_srv_user_status 
     WHERE userid=? AND survey_id=?
  SQL;

  $row = MySQLFetchOneAssoc($query, 'si', $userid, $survey_id);

  if (empty($row['timestamp'])) { return []; }
  return $row;
}

/**
 * Updates the draft or submitted responses for the specified user
 * @param string $userid 
 * @param int $survey_id 
 * @param array $responses 
 * @param bool $draft  true:update draft, false:update submitted
 * @return void 
 */
function update_user_responses(string $userid,int $survey_id,array $responses,bool $draft) : void
{
  // wrap all database updates in a transaction to allow for rollback on failure
  MySQLBeginTransaction();

  try {
    // remove the existing reponses
    //   if saving a draft, only remove the draft responses
    //   if submitting, remove all responses 
    $query = $draft 
    ? 'DELETE from tlc_srv_responses WHERE userid=? AND survey_id=? AND draft=1'
    : 'DELETE from tlc_srv_responses WHERE userid=? AND survey_id=?';
    MySQLExecute($query, 'si', $userid, $survey_id);

    // update the user status table
    if($draft) {
      $query = <<<SQL
        INSERT into tlc_srv_user_status (userid,survey_id,draft)
        VALUES (?,?,CURRENT_TIMESTAMP)
        ON DUPLICATE KEY 
           UPDATE draft=CURRENT_TIMESTAMP
      SQL;
    } else {
      $query = <<<SQL
        INSERT into tlc_srv_user_status (userid,survey_id,submitted)
        VALUES (?,?,CURRENT_TIMESTAMP)
        ON DUPLICATE KEY 
           UPDATE draft=NULL, submitted=CURRENT_TIMESTAMP, email_sent=NULL, sent_to=NULL
      SQL;
    }
    MySQLExecute($query, 'si', $userid, $survey_id);
  
    foreach( $responses as $k=>$v )
    {
      // skip any empty input responses
      if( $v==='' ) { continue; }

      // Freetext questions
      if(preg_match('/^question-freetext-(\d+)$/',$k,$m)) {
        $query = <<<SQL
           INSERT into tlc_srv_responses (userid,survey_id,question_id,draft,free_text)
           VALUES     (?,?,?,$draft,?)
           ON DUPLICATE KEY UPDATE free_text=?;
        SQL;
        MySQLExecute($query,'siiss', $userid, $survey_id, $m[1], $v, $v);
      }

      // Boolean questions
      elseif(preg_match('/^question-bool-(\d+)$/',$k,$m)) {
        $query = <<<SQL
           INSERT into tlc_srv_responses (userid,survey_id,question_id,draft,selected)
           VALUES     (?,?,?,$draft,1)
           ON DUPLICATE KEY UPDATE selected=1;
        SQL;
        MySQLExecute($query,'sii', $userid, $survey_id, $m[1]);
      }

      // Single and multi select questions
      elseif(preg_match('/^question-select-(\d+)$/',$k,$m)) {
        $query = <<<SQL
           INSERT into tlc_srv_responses (userid,survey_id,question_id,draft,selected)
           VALUES     (?,?,?,$draft,?)
           ON DUPLICATE KEY UPDATE selected=?;
        SQL;
        MySQLExecute($query,'siiii', $userid, $survey_id, $m[1],$v,$v);
      }
      elseif(preg_match('/^question-multi-(\d+)-(\d+)$/',$k,$m)) {
        // need an entry in both the responses and the response options tables
        $query = <<<SQL
           INSERT IGNORE into tlc_srv_responses (userid,survey_id,question_id,draft)
           VALUES     (?,?,?,$draft);
        SQL;
        MySQLExecute($query,'sii', $userid, $survey_id, $m[1]);

        $query = <<<SQL
           INSERT into tlc_srv_response_options (userid,survey_id,question_id,draft,option_id)
           VALUES     (?,?,?,$draft,?);
        SQL;
        MySQLExecute($query,'siii', $userid, $survey_id, $m[1],$m[2]);
      }
      elseif(preg_match('/^question-(?:multi|select)-(\d+)-has-other$/',$k,$m)) {
        $query = <<<SQL
           INSERT into tlc_srv_responses (userid,survey_id,question_id,draft,selected)
           VALUES     (?,?,?,$draft,0)
           ON DUPLICATE KEY UPDATE selected=0;
        SQL;
        MySQLExecute($query,'sii', $userid, $survey_id, $m[1]);
      }
      elseif(preg_match('/^question-(?:multi|select)-(\d+)-other/',$k,$m)) {
        $query = <<<SQL
           INSERT into tlc_srv_responses (userid,survey_id,question_id,draft,other)
           VALUES     (?,?,?,$draft,?)
           ON DUPLICATE KEY UPDATE other=?;
        SQL;
        MySQLExecute($query,'siiss', $userid, $survey_id, $m[1],$v, $v);
      }

      // Add qualifiers
      elseif(preg_match('/^question-qualifier-(\d+)$/',$k,$m)) {
        $query = <<<SQL
           INSERT into tlc_srv_responses (userid,survey_id,question_id,draft,qualifier)
           VALUES     (?,?,?,$draft,?)
           ON DUPLICATE KEY UPDATE qualifier=?;
        SQL;
        MySQLExecute($query,'siiss', $userid, $survey_id, $m[1],$v,$v);
      }
    }

    MySQLCommit();
  }
  catch(mysqli_sql_exception $e)
  {
    log_warning("Failed to update responses from $userid: " . $e->getMessage());
    MySQLRollback();
  }
}
