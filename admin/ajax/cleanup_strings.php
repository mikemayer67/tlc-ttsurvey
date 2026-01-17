<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-cleanup');
$response = new AjaxResponse();

start_ob_logging();

$rows = MySQLSelectValues('select string_id from tlc_tt_view_unused_strings');
$response->add('count',count($rows));

if($rows) 
{
  $unused_ids = implode(',', array_map('intval',$rows));
  $rc = MySQLExecute("delete from tlc_tt_strings where string_id in ($unused_ids)");
}

end_ob_logging();

$response->send();
die();
