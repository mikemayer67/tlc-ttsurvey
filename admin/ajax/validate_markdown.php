<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

handle_warnings();


log_dev("Validate Markdown POST: ".print_r($_POST,true));
$markdown   = $_POST['markdown'];

// not yet implementing allowing links, but just to set the hooks
// if/when this capability is added
$allow_link = $_POST['allow_links'] ?? false;

todo("flesh out markdown validation");
// TODO: Flesh out markdown validation

// pick one of the following for testing
$response = ['success'=>false, 'findings'=>['Bad stuff happened']];
//$response = ['success'=>true];

echo json_encode($response);
die();
