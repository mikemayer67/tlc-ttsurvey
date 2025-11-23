<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

validate_ajax_nonce('admin-cleanup');

start_ob_logging();

$rows = MySQLSelectValues('select string_id from tlc_tt_view_unused_strings');

$nrows = count($rows);
if( $nrows > 0 )
{
  $unused_ids = implode(',', array_map('intval',$rows));

  $rc = MySQLExecute("delete from tlc_tt_strings where string_id in ($unused_ids)");

  $rval = array('success'=>true, 'count'=>$nrows);
}
else
{
  $rval = array('success'=>true, 'count'=>0);
}

end_ob_logging();

echo json_encode($rval);
die();
