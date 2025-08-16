<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

validate_and_retain_nonce('admin-surveys','GET');

require_once(app_file('include/logger.php'));
require_once(app_file('include/surveys.php'));

class Exception extends \Exception {}

try {
  $survey_id = $_REQUEST['pdf'];
  if(!$survey_id) { throw new Exception('no survey id'); }

  $survey_info = survey_info($survey_id);
  if(!$survey_info) { throw new Exception('invalid survey id'); }

  $survey_file = survey_pdf_file($survey_id);
  if(!file_exists($survey_file)) { throw new Exception('no survey pdf file'); }

  $data = file_get_contents($survey_file);
  if(!$data) { throw new Exception('failed to load pdf content'); }

  $len = strlen($data);
  $filename = str_replace(' ','',$survey_info['title'] . '.pdf');

  header('Content-Type: application/pdf');
  header("Content-Disposition: attachment; filename=\"$filename\"");
  header("Content-Length: $len");
  header('Cache-Control: no-cache, must-revalidate');
  header('Expires: 0');
  echo $data;
}
catch(Exception $e)
{
  log_warning('Failed to download pdf: '.$e->getMessage());
  http_response_code(404);
  header('Content-Type: text/plain');
  echo "Could not find the requested Survey PDF file";
}

die();
