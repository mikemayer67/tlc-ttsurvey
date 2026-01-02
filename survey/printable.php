<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));
require_once(app_file('survey/print_render.php'));
require_once(app_file('vendor/autoload.php'));

use tecnickcom\tcpdf\TCPDF;

log_dev("-------------- Start of Printable --------------");

validate_and_retain_get_nonce('admin-surveys');

$survey_id=$_GET['printable'];

$info = survey_info($survey_id);
if(!$info) { api_die(); }

$content = survey_content($survey_id);

// Create TCPDF instance
$tcpdf = new TCPDF(
    'P', // Portrait
    'mm', // Millimeters
    'LETTER', // Page size
    true, // Unicode
    'UTF-8', // Encoding
    false // Disk cache disabled  
);

$tcpdf->SetMargin(10, 22, 20); // left, top, right

// $mpdf = new Mpdf([
//     'margin_header'     => 5,
//     'margin_top'        => 22,
//     'margin_bottom'     => 20,
//     'margin_footer'     => 5,
//     'margin_right'      => 10,
//     'margin_left'       => 10,
//     'fontDir'           => $fontDir,
//     'fontdata'          => $fontData,
//     'default_font'      => 'ttt_sans_serif',
//     'default_font_size' => 10,
//     'tempDir'           => app_file('vendor/__cache'),
// ]);

ob_start();
render_printable($tcpdf,$content);
ob_end_clean();

// $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

// Output PDF inline to the tab opened by JS
//echo $html;
// $mpdf->Output("survey_{$survey_id}.pdf", \Mpdf\Output\Destination::INLINE);

die();

