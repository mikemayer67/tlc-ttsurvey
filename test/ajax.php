<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

$config = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true,INI_SCANNER_TYPED);
if( ($config['dev_env']??false) !== true ) {
  api_die("Attempted to load ajax testing in a non-dev environment");
}

$nonce_keys = [
  'admin-navbar','admin-cleanup','admin-log','admin-login',
  'admin-participants','admin-roles','admin-settings','admin-surveys',
  'preview','update-page','summary-download','survey-form',
  'login','pwreset','recover','register','updateprof','updatepw',
];
$_SESSION['nonce'] = [];
foreach($nonce_keys as $k) { $_SESSION['nonce'][$k] = "AJAXTEST"; }

$test = $_GET['ajaxtest'] ?? '';
if($test === 'get')
{
  foreach ($_GET as $k => $v) { $_POST[$k] = $v; }
  return;
}
elseif($test === 'all') 
{
  require_once(app_file('include/elements.php'));

  start_header('AJAX Testing');
  $vr = rand();
  echo "<script src='js/jquery-3.7.1.min.js'></script>";
  echo "<script src='test/ajax.js'></script>";
  echo "<link rel='stylesheet' type='text/css' href='test/ajax.css?v=$vr'>";
  end_header();

  $ajaxuri = app_uri();
  echo "<input id='ajaxuri' type='hidden' value='$ajaxuri'>";

  echo "<h2>Ajax Testing</h2>";
  echo "<p><b>All of your open forms are now hosed... their nonces must be refreshed</b></p>";
  echo "<table id='results'><tr>";
  echo "<th>API</th><th>Caller</th><th>Inputs</th><th>Result</th>";
  echo "</tr></table>";

  die();
}
api_die("Unknown ajax test: '$test'");