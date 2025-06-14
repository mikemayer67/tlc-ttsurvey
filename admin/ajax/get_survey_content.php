<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));

validate_ajax_nonce('admin-surveys');

handle_warnings();

log_dev('get_survey_content: '.print_r($_POST,true));

$id = $_POST['survey_id'];

$content = survey_content($id);

if($content) { 
  $response = array('success'=>true, 'content'=>$content);
} else {
  $response = array('success'=>false, 'error'=>'No content defined for selected survey');
}

echo json_encode($response);
die();
