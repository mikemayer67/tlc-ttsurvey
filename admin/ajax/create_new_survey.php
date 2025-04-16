<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/surveys.php'));

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

if($new_id) {
  $rval = array('success'=>true);
} else {
  $rval = array('success'=>false, 'error'=>$error);
}

echo json_encode($rval);
die();
