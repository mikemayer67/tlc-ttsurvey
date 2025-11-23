<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

log_dev("cleanup options");

validate_ajax_nonce('admin-cleanup');

start_ob_logging();

$rows = MySQLSelectArrays('select survey_id, survey_rev, option_id from tlc_tt_view_unused_options');

log_dev("Rows: ".print_r($rows,true));

$nrows = count($rows);
if( $nrows > 0 )
{
  $query = 'delete from tlc_tt_survey_options where survey_id=? and survey_rev=? and option_id=?';
  foreach($rows as [$sid,$srev,$oid]) {
    $rc = MySQLExecute($query,'iii',$sid,$srev,$oid);
  }
  $rval = array('success'=>true, 'count'=>$nrows);
}
else
{
  $rval = array('success'=>true, 'count'=>0);
}

end_ob_logging();

log_dev("rval: ".print_r($rval,true));

echo json_encode($rval);
die();
