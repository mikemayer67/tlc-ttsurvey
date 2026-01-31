<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

// no need to do anything ... this just keeps the local session active

http_response_code(200);
echo json_encode(['success'=>true]);
die();