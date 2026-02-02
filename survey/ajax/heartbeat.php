<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('survey/elements.php'));

// check for updates from a different session

$userid    = $_POST['userid'];
$survey_id = $_POST['survey_id'];
$cur_ts    = user_status_timestamps($userid,$survey_id);
$new_ts    = $cur_ts;
$form_ts   = $_POST['timestamps'] ?? 'null:null';

$cur_ts  = explode(':',$cur_ts);
$form_ts = explode(':',$form_ts);

$response = ['success'=>true, 'modified'=>false];

if($cur_ts[0] !== $form_ts[0]) { $response['modified'] = 'draft';     }
if($cur_ts[1] !== $form_ts[1]) { $response['modified'] = 'submitted'; }

if($response['modified']) { $response['new_timestamps'] = $new_ts; }

http_response_code(200);
echo json_encode($response);
die();