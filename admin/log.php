<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

validate_and_retain_nonce('admin-log','GET');

require_once(app_file('include/logger.php'));

$dest = $_REQUEST['log'] ?? 'newtab';

$log_file = log_file();
fflush(logger());

$data = file_get_contents($log_file);

if('newtab' === $dest)
{
  echo "<pre>", $data, "</pre>";
} 
else {
  $len = strlen($data);
  $filename = str_replace(' ','',app_name()) . '.log';
  header('Content-Type: text/plain');
  header("Content-Disposition: attachment; filename=\"$filename\"");
  header("Content-Length: $len");
  header('Cache-Control: no-cache, must-revalidate');
  header('Expires: 0');
  echo $data;
}

die();


