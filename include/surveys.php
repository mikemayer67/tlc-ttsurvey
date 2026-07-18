<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/question_flags.php'));
require_once(app_file('include/question_types.php'));
require_once(app_file('include/survey_content.php'));

/**
 * Returns the survey ID of the active survey
 * @return null|int 
 */
function active_survey_id() : ?int 
{ 
  $ids = MySQLFetchColumn("select survey_id from tlc_srv_active_surveys");
  if(count($ids)>1) {
    internal_error("Multiple active surveys found in the database: ".implode(', ',$ids));
  }
  return $ids[0] ?? null;
}

/**
 * Returns the name of the active survey
 * @return null|string 
 */
function active_survey_title() : ?string 
{
  $titles = MySQLFetchColumn("select title from tlc_srv_active_surveys");
  if (count($titles) > 1) {
    internal_error("Multiple active surveys found in the database: " . implode(', ', $titles));
  }
  return $titles[0] ?? null;
}

/**
 * Returns an associative array containing all of the info about the specified survey
 *   that is found in the database:  
 *   - survey_id : ID of the survey iteself
 *   - id : Same as survey_id, but needed by javascript
 *   - parent_id : ID of the survey from which this survey was cloned (null if not cloned)
 *   - title : name of the survey
 *   - created : date/time the survey was created
 *   - modified  : date/time the survey content/structure was last modified
 *   - active : date/time the survey went active (null if not active or closed)
 *   - closed : date/time the survey was closed (null if still draft or active)
 * @param int $id, the survey of interest
 * @return null|array 
 */
function survey_info($id) : ?array 
{
  $info = MySQLFetchOneAssoc("select * from tlc_srv_surveys where survey_id=?", 'i', $id);
  if (!$info) { return null; }
  $info['id'] = $info['survey_id']; // need to add this as it is not from database
  return $info;
}

/**
 * Returns for each survey in the dataase an associative array containing the following
 *   info about each:
 *   - survey_id : ID of the survey iteself
 *   - parent_id : ID of the survey from which this survey was cloned (null if not cloned)
 *   - status : draft, active, or closed
 *   - title : name of the survey
 *   - created : date/time the survey was created
 *   - modified  : date/time the survey content/structure was last modified
 *   - active : date/time the survey went active (if active or closed)
 *   - closed : date/time the survey was closed (if closed)
 *  The order of the returned surveys will always be:
 *   - active survey (at most one)
 *   - draft surveys (unspecified order)
 *   - closed surveys (unspecified order)
 * @return array 
 */
function all_surveys() : array 
{
  $surveys = [];

  $active = MySQLFetchAllAssoc('select * from tlc_srv_active_surveys');
  $drafts = MySQLFetchAllAssoc('select * from tlc_srv_draft_surveys');
  $closed = MySQLFetchAllAssoc('select * from tlc_srv_closed_surveys');

  $nactive = count($active);
  if($nactive) {
    if($nactive>1) { internal_error('Multiple active surveys found'); }
    $survey = $active[0];
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

  return $surveys;
}