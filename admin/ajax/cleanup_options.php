<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/ajax.php'));

log_dev("__Option Cleanup__");

validate_ajax_nonce('admin-cleanup');
$response = new AjaxResponse();

start_ob_logging();

$rows = MySQLSelectArrays('select survey_id, option_id from tlc_tt_view_unused_options');
$response->add('count', count($rows));

if($rows)
{
  $query = 'delete from tlc_tt_survey_options where survey_id=? and option_id=?';
  foreach($rows as [$sid,$oid]) {
    $rc = MySQLExecute($query,'ii',$sid,$oid);
  }
}

end_ob_logging();

$response->send();
die();
