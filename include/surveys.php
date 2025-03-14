<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));


function active_survey_id()
{
  $ids = MySQLSelectArrays("select id from tlc_tt_active_surveys");
  log_dev("active_survey_id ids: ".print_r($ids,true));

  switch(count($ids)) {
  case 0: return null;    break;
  case 1: return $ids[0]; break;
  default:
    internal_error("Multiple active surveys found in the database: ".implode(', ',$ids));
    break;
  }
}

function active_survey_title()
{
  $id = active_survey_id();
  if(!isset($id)) { return "Time and Talent Survey"; }

  $result = MySQLSelectArray( "select title from  tlc_tt_active_surveys where id=?",'i',$id);
  log_dev("active_survey_title result: ".print_r($result,true));

  return $result[0];
}

// returns the ids of all draft surveys
function draft_survey_ids()
{
  $result = MySQLSelectArrays("select id from tlc_tt_draft_surveys");
  log_dev("active_draft_id ids: ".print_r($result,true));

  $ids = array();
  foreach($result as $id) { $ids[] = $id[0]; }

  return $ids;
}

