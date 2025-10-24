<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

$action = $_POST['action'];
log_dev("-------------- Handle Action: $action --------------");

log_dev("POST: ".print_r($_POST,true));

die();
