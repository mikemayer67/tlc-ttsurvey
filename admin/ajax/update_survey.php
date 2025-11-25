<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('admin/surveys/update.php'));

validate_ajax_nonce('admin-surveys');

start_ob_logging();

use Exception;

// using javascript key id rather than PHP key survey_id
$survey_id  = $_POST['id'] ?? null;
$title      = $_POST['name'] ?? null;
$content    = json_decode($_POST['content'],true);

try {
  $error = null;

  if(!$survey_id)  { throw new Exception('Missing survey_id in request'); }

  update_survey($survey_id,$content,$title);

  $next_ids = next_survey_ids($survey_id);
  $revised_content = survey_content($survey_id);

  $rval = array(
    'success'=>true,
    'content'=>$revised_content,
    'next_ids' => $next_ids,
  );
}
catch(Exception $e)
{
  $errid = bin2hex(random_bytes(3));
  $error = $e->getMessage();
  log_error("[$errid]: $error",0);
  http_response_code(400);
  $rval = array(
    'success'=>false, 
    'error'=>"Failed to update survey.  Please report error $errid to a tech admin",
  );
}

end_ob_logging();

echo json_encode($rval);
die();
