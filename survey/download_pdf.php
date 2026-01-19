<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));
require_once(app_file('include/elements.php'));
require_once(app_file('survey/print_render.php'));

log_dev("-------------- Start of Preview --------------");

validate_and_retain_get_nonce('admin-surveys');

$survey_id=$_GET['sid'];

$page_title = 'Printable Survey';
$info    = survey_info($survey_id);
if(!$info) { die(); }

$title   = $info['title'];
$content = survey_content($survey_id);

// Add html header
start_header($title);
add_tab_name('ttt_printable');
$uri = css_uri('printable');
echo "<link rel='stylesheet' type='text/css' href='$uri'>";
add_js_resources('printable',js_uri('printable','survey'));
end_header();

// Add print size note
echo "<div id='print-warning'>";
echo "Note: This page is displayed at actual print size. It may look small on-screen, but it will print correctly.";
echo "</div>";

// Start content
echo "<div id='content'>";

// Add page header

$logo_file = app_logo();
$logo_uri  = $logo_file ? img_uri($logo_file) : '';
echo "<div class='ttt-header'>";
if($logo_uri) { echo "<img class='ttt-logo' src='$logo_uri'>"; }
echo "<span class='ttt-title'>$title</span>";
echo "</div>";


// Render conttent
render_printable($content);

echo "</div>"; // content
echo "</body>";
