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

function all_surveys()
{
  return array(
    'drafts' => MySQLSelectRows( 
      'select id,title,unix_timestamp(created) created from tlc_tt_surveys'
      .' where active is NULL and closed is NULL'
      .' order by created'
    ),
    'active' => MySQLSelectRows(
      'select id,title,unix_timestamp(active) active from tlc_tt_surveys'
      .' where active is not NULL and closed is NULL'
      .' order by active'
    ),
    'closed' => MySQLSelectRows(
      'select id,title,unix_timestamp(closed) closed from tlc_tt_surveys'
      .' where closed is not NULL'
      .' order by closed desc'
    ),
  );
}
