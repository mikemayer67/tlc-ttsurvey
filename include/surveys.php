<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));


function active_survey_id()
{
  $ids = MySQLSelectArrays("select id from tlc_tt_active_surveys");

  switch(count($ids)) {
  case 0: return null;    break;
  case 1: return $ids[0][0]; break;
  default:
    internal_error("Multiple active surveys found in the database: ".implode(', ',$ids));
    break;
  }
}

function active_survey_title()
{
  $id = active_survey_id();
  if(isset($id)) { 
    $result = MySQLSelectArray( "select title from  tlc_tt_active_surveys where id=?",'i',$id);
    log_dev("active_survey_title: ".print_r($result,true));
    $title = $result[0] ?? null;
  }
  return $title ?? null;
}

// returns the ids of all draft surveys
function draft_survey_ids()
{
  $result = MySQLSelectArrays("select id from tlc_tt_draft_surveys");

  $ids = array();
  foreach($result as $id) { $ids[] = $id[0]; }

  return $ids;
}

