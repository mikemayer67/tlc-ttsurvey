<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

validate_ajax_nonce('admin-log');

start_ob_logging();

$level = $_POST['level'] ?? 2;
$lines = $_POST['lines'] ?? null;

$log_file = log_file();
fflush(logger());

$rval = [];
$fp = fopen($log_file,"r");
if($fp) {
  $matching = false;
  while(($line = fgets($fp))!==false) {
    $line = rtrim($line);
    if($line==='') { continue; }
    if(preg_match('/^\[.+?\]\s+(DEV|INFO|WARNING|ERROR|TODO)/',$line,$m)) {
      switch($m[1]) {
      case 'DEV':
        $matching = ($level > 2);
        break;
      case 'INFO':
      case 'TODO':
        $matching = ($level > 1);
        break;
      case 'WARNING':
        $matching = ($level > 0);
        break;
      case 'ERROR':
        $matching = true;
        break;
      }
      if($matching) { $rval[] = $line; }
    }
    elseif($matching) {
      $rval[] = array_pop($rval) . "\n" . $line;
    }
  }
}

if($lines) { 
  $rval = array_splice($rval,-1*$lines);
}

end_ob_logging();

echo json_encode($rval);
die();
