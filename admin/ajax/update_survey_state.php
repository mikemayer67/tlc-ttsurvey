<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('admin/surveys/update.php'));

validate_ajax_nonce('admin-surveys');

start_ob_logging();

use Exception;

$survey_id  = $_POST['survey_id'] ?? null;
$new_state  = $_POST['new_state'] ?? null;

try {
  $error = null;

  if(!$survey_id) { throw new Exception('Missing survey_id in request'); }
  if(!$new_state) { throw new Exception('Missing new_state in request');  }

  $message = '';
  $success = update_survey_state($survey_id,$new_state,$message);

  log_info($message);

  $rval = array(
    'success'=>$success,
    'message'=>$message,
  );
}
catch(Exception $e)
{
  log_dev("Somthing went wrong in " . $e->getFile() . " on line " . $e->getLine() . ": " . $e->getMessage(), 0);
  $errid = bin2hex(random_bytes(3));
  $error = $e->getMessage();
  log_error("[$errid]: $error",0);
  http_response_code(400);
  $rval = array(
    'success'=>false, 
    'error'=>"Failed to update survey state.  Please report error $errid to a tech admin",
  );
}

end_ob_logging();

echo json_encode($rval);
die();
