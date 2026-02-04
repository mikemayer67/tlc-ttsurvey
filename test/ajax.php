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
  echo "<script type='module' src='test/ajax.js'></script>";
  echo "<link rel='stylesheet' type='text/css' href='test/ajax.css?v=$vr'>";
  end_header();

  $ajaxuri = app_uri();
  echo "<input id='ajaxuri' type='hidden' value='$ajaxuri'>";

  $config = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true);
  $admin_username = $config['admin_username'] ?? null;
  $admin_password = $config['admin_password'] ?? null;
  if($admin_username) { echo "<input id='admin-username' type='hidden' value='$admin_username'>"; }
  if($admin_password) { echo "<input id='admin-password' type='hidden' value='$admin_password'>"; }

  $active_survey_id = active_survey_id();
  if($active_survey_id) { echo "<input id='survey-id' type='hidden' value='$active_survey_id'>"; }

  $all_userids = MySQLSelectValues('select userid from tlc_tt_userids','');
  $all_userids = json_encode($all_userids);
  echo "<input id='all-userids', type='hidden', value='$all_userids'>";

  $smtp_inputs = [
    'smtp_host'=>Settings::get('smtp_host'),
    'smtp_username'=>Settings::get('smtp_username'),
    'smtp_password'=>Settings::get('smtp_password'),
    'smtp_auth'=>Settings::get('smtp_auth'),
    'smtp_port'=>Settings::get('smtp_port'),
    'reply_email'=>Settings::get('smtp_reply_email'),
    'reply_name'=>Settings::get('smtp_reply_name'),
  ];
  $smtp_inputs = json_encode($smtp_inputs);
  echo "<input id='smtp-inputs', type='hidden', value='$smtp_inputs'>";


  echo "<h2>Ajax Testing</h2>";

  echo "<table class='inputs'><tr>";
  echo "<td class='key'>Userid</td>";
  echo "<td><input id='userid' placeholder='optional'></td>";
  echo "</tr><tr>";
  echo "<td class='key'>Password</td>";
  echo "<td><input id='passwd' type='password' placeholder='optional'></td>";
  echo "</tr><tr>";
  echo "<td colspan=2><button id='run_all_tests'>Run All Tests</td>";
  echo "</tr></table>";

  echo "<table class='results'><tr>";
  echo "<th>API</th><th>Caller</th><th>Inputs</th><th>Expected</th><th>Result</th>";
  echo "</tr></table>";

  die();
}
api_die("Unknown ajax test: '$test'");