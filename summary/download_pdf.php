<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));
require_once(app_file('include/survey_content.php'));
require_once(app_file('include/responses.php'));
require_once(app_file('pdf/summary_pdf.php'));
require_once(app_file('summary/summary_content.php'));

$survey_id = $_REQUEST['sid'] ?? active_survey_id();

$info = survey_info($survey_id);
if(!$info) { api_die(); }
$title = $info['title'];

$survey_content   = SurveyContent::fromSurveyID($survey_id);
$responses        = get_all_responses($survey_id);
$summary_content  = new SummaryContent($survey_content, $responses);

ob_start();
$summary_pdf = new SummaryPDF("Response Summary: $title");
$summary_pdf->render($summary_content);
ob_end_clean();

return $summary_pdf->Output('summary.pdf','I');

die();