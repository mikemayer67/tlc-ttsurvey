<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/surveys.php'));

validate_ajax_nonce('admin-surveys');

handle_warnings();

use Exception;

$id      = $_POST['survey_id'] ?? null;
$rev     = $_POST['revision'] ?? null;
$name    = $_POST['name'] ?? null;
$cur_pdf = $_POST['existing_pdf'] ?? 'keep';
$new_pdf = $_FILES['survey_pdf']['tmp_name'] ?? null;
$content = json_decode($_POST['content'],true);

log_dev("update_survey: id = $id");
log_dev("update_survey: rev = $rev");
log_dev("update_survey: name = $name");
log_dev("update_survey: cur_pdf = $cur_pdf");
log_dev("update_survey: new_pdf = $new_pdf");
//log_dev("update_survey: content = " . print_r($content,true));


try {
  $error = null;
  if(!$id) {
    throw new Exception('Missing id in request');
  }

  if(!update_survey($id,$rev,$name,$cur_pdf,$new_pdf,$content,$error)) {
    log_dev("oops... error = $error");
    throw new Exception($error);
  }

  $rval = array(
    'success'=>true,
    'has_pdf'=>(null !== survey_pdf_file($id)),
    'next_ids' => next_ids($id),
  );
}
catch(Exception $e)
{
  $errid = bin2hex(random_bytes(3));
  $error = $e->getMessage();
  log_error("[$errid]: $error");
  http_response_code(400);
  $rval = array(
    'success'=>false, 
    'error'=>"Internal error #$errid: Please let a tech admin know.",
  );
}

echo json_encode($rval);
die();
