<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

log_dev("__Option Cleanup__");

validate_ajax_nonce('admin-cleanup');

start_ob_logging();

$rows = MySQLSelectArrays('select survey_id, option_id from tlc_tt_view_unused_options');

log_dev("Rows: ".print_r($rows,true));

$nrows = count($rows);
if( $nrows > 0 )
{
  $query = 'delete from tlc_tt_survey_options where survey_id=? and option_id=?';
  foreach($rows as [$sid,$oid]) {
    $rc = MySQLExecute($query,'ii',$sid,$oid);
  }
  $rval = array('success'=>true, 'count'=>$nrows);
  log_dev("  $nrow unused options removed");
}
else
{
  $rval = array('success'=>true, 'count'=>0);
  log_dev("  No unused options found");
}

end_ob_logging();

echo json_encode($rval);
die();
