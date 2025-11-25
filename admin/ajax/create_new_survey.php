<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('admin/surveys/create_new.php'));

validate_ajax_nonce('admin-surveys');

start_ob_logging();

$name = $_POST['name'] ?? null;
if(!$name) {
  echo json_encode( array( 'success'=>false, 'name'=>'mising' ) );
  die();
}

$clone = $_POST['clone'] ?? null;

$error = null;
$new_id = create_new_survey($name,$clone,$error);

if($new_id) {
  $info = survey_info($new_id);
  $rval = array('success'=>true, 'survey'=>$info);
} else {
  $rval = array('success'=>false, 'error'=>$error);
}

end_ob_logging();

echo json_encode($rval);
die();
