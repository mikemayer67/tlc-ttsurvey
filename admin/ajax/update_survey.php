<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('admin/surveys/update.php'));

validate_ajax_nonce('admin-surveys');

start_ob_logging();

use Exception;

// using javascript keys id and revision rather than PHP keys survey_id and survey_rev
$survey_id  = $_POST['id'] ?? null;
$survey_rev = $_POST['revision'] ?? null;
$title      = $_POST['name'] ?? null;
$pdf_action = $_POST['pdf_action'] ?? null;
$new_pdf    = $_FILES['new_survey_pdf']['tmp_name'] ?? null;
$content    = json_decode($_POST['content'],true);

try {
  $error = null;

  if(!$survey_id)  { throw new Exception('Missing survey_id in request'); }
  if(!$survey_rev) { throw new Exception('Missing revision in request');  }

  $details = [];
  if($title)      { $details['title']      = $title;      }
  if($pdf_action) { $details['pdf_action'] = $pdf_action; }
  if($new_pdf)    { $details['new_pdf']    = $new_pdf;    }

  update_survey($survey_id,$survey_rev,$content,$details);

  $pdf_file = survey_pdf_file($survey_id);
  $next_ids = next_survey_ids($survey_id);
  $revised_content = survey_content($survey_id);

  $rval = array(
    'success'=>true,
    'has_pdf'=>($pdf_file !== null),
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
