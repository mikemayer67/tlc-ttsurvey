<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));
require_once(app_file('survey/print_render.php'));

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\HTMLParserMode;

require_once app_file('vendor/autoload.php');

log_dev("-------------- Start of Printable --------------");

validate_and_retain_get_nonce('admin-surveys');

$survey_id=$_GET['printable'];

$info = survey_info($survey_id);
if(!$info) { app_die(); }

$content = survey_content($survey_id);

// Create mPDF instance

$configVars      = new ConfigVariables();
$defaultConfig   = $configVars->getDefaults();
$fontDir         = $defaultConfig['fontDir'];
$fontDir[]       = app_file('fonts');

$fontVars          = new FontVariables();
$defaultFontConfig = $fontVars->getDefaults();
$fontData          = $defaultFontConfig['fontdata'];

$fontData['ttt_serif'] = [
  'R'  => 'noto_serif_display/NotoSerifDisplay-Regular.ttf',
  'I'  => 'noto_serif_display/NotoSerifDisplay-Italic.ttf',
  'B'  => 'noto_serif_display/NotoSerifDisplay-Bold.ttf',
  'BI' => 'noto_serif_display/NotoSerifDisplay-BoldItalic.ttf',
];
$fontData['ttt_sans_serif'] = [
  'R'  => 'quicksand/Quicksand-Regular.ttf',
  'B'  => 'quicksand/Quicksand-Bold.ttf',
];
$fontData['ttt_fixed_width'] = [
  'R'  => 'courier_prime/CourierPrime-Regular.ttf',
  'I'  => 'courier_prime/CourierPrime-Italic.ttf',
  'B'  => 'courier_prime/CourierPrime-Bold.ttf',
  'BI' => 'courier_prime/CourierPrime-BoldItalic.ttf',
];

$mpdf = new Mpdf([
    'mode'              => 'utf-8',
    'format'            => 'Letter',
    'margin_header'     => 5,
    'margin_top'        => 22,
    'margin_bottom'     => 20,
    'margin_footer'     => 5,
    'margin_right'      => 10,
    'margin_left'       => 10,
    'fontDir'           => $fontDir,
    'fontdata'          => $fontData,
    'default_font'      => 'ttt_sans_serif',
    'default_font_size' => 10,
    'tempDir'           => app_file('vendor/__cache'),
]);

$css = file_get_contents(app_file('css/printable.css'));
$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

// Rather than requiring the render function to build a single html string, 
//   we will use output buffering and let the function think it's writing
//   directly to the browswer.
ob_start();
render_header($info['title']);
$html = ob_get_clean();
$mpdf->SetHTMLHeader($html);

ob_start();
render_footer($content);
$html = ob_get_clean();
$mpdf->SetHTMLFooter('','1');
$mpdf->SetHTMLFooter($html,'2-');

ob_start();
render_printable($mpdf,$content);
$html = ob_get_clean();

// For debugging html that would otherwise go to mPDF for rendering.
if (!empty($_GET['debug_html'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><style>$css</style></head><body>$html</body></html>";
    die();
}

$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

// Output PDF inline to the tab opened by JS
//echo $html;
$mpdf->Output("survey_{$survey_id}.pdf", \Mpdf\Output\Destination::INLINE);

die();

