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
  $surveys = array();

  $drafts = MySQLSelectRows( 
    'select * from tlc_tt_surveys where active is NULL order by created'
  );
  $active = MySQLSelectRows( 
    'select * from tlc_tt_surveys where active is not NULL and closed is NULL order by active'
  );
  $closed = MySqlSelectRows(
    'select * from tlc_tt_surveys where closed is not NULL order by closed'
  );

  if($drafts) {
    $surveys['drafts'] = array();
    foreach($drafts as $survey) {
      $survey['has_pdf'] = (null !== survey_pdf_file($survey['id']));
      $surveys['drafts'][] = $survey;
    }
  }
  if($active) {
    $surveys['active'] = array();
    foreach($active as $survey) {
      $survey['has_pdf'] = (null !== survey_pdf_file($survey['id']));
      $surveys['active'][] = $survey;
    }
  }
  if($closed) {
    $surveys['closed'] = array();
    foreach($closed as $survey) {
      $survey['has_pdf'] = (null !== survey_pdf_file($survey['id']));
      $surveys['closed'][] = $survey;
    }
  }

  return $surveys;
}

function survey_pdf_file($survey_id)
{
  $pdf_file = app_file("pdf/survey_$survey_id.pdf");
  if(file_exists($pdf_file)) {
    return $pdf_file;
  } else {
    return null;
  }
}
