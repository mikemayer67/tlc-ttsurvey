<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('admin/surveys/create_new.php'));

validate_ajax_nonce('admin-surveys');

handle_warnings();

$name = $_POST['name'] ?? null;
if(!$name) {
  echo json_encode( array( 'success'=>false, 'name'=>'mising' ) );
  die();
}

$clone   = $_POST['clone'] ?? null;
$tmp_pdf = $_FILES['survey_pdf']['tmp_name'] ?? null;

$error = null;
$new_id = create_new_survey($name,$clone,$tmp_pdf,$error);
log_dev("create_new_survey: new_id=$new_id");

if($new_id) {
  $info = survey_info($new_id);
  $rval = array('success'=>true, 'survey'=>$info);
} else {
  $rval = array('success'=>false, 'error'=>$error);
}

log_dev("rval: ".print_r($rval,true));
echo json_encode($rval);
die();
