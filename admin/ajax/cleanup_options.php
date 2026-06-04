<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-cleanup');
$response = new AjaxResponse();

start_ob_logging();

$rows = MySQLFetchAllIndexed('select survey_id, option_id from tlc_srv_view_unused_options');
$response->add('count', count($rows));

if($rows)
{
  $query = 'delete from tlc_srv_survey_options where survey_id=? and option_id=?';
  foreach($rows as [$sid,$oid]) {
    $rc = MySQLExecute($query,'ii',$sid,$oid);
  }
}

end_ob_logging();

$response->send();
die();
