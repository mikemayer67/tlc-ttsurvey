<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Preview --------------");

log_dev("NONCES: ".print_r($_SESSION['nonce'],true));
validate_and_retain_get_nonce('admin-surveys');

$survey_id=$_GET['printable'];

echo "Show survey $survey_id";
