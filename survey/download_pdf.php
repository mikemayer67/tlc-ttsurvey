<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));
require_once(app_file('pdf/survey_pdf.php'));

log_dev("-------------- Start of Survey PDF Download --------------");

validate_and_retain_get_nonce('admin-surveys');

$survey_id=$_GET['sid'];

$info = survey_info($survey_id);
if(!$info) { api_die(); }

$content = survey_content($survey_id);

ob_start();

$survey_pdf = new SurveyPDF();
$survey_pdf->render($info, $content);

ob_end_clean();

return $survey_pdf->Output('survey.pdf','I');

die();

