<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('summary/csv_generator.php'));

$survey_id = $_REQUEST['sid'] ?? active_survey_id();

$cg = new CSVGenerator($survey_id);
$cg->render();

die();