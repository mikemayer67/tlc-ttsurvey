<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/settings.php');

function log_file() 
{ 
  static $log_file = null;
  if(!$log_file) {
    $config = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true);
    $log_file = $config['log_file'] ?? PKG_NAME.'.log';
  }
  return $log_file;
}

function logger()
{
  static $_fp = null;

  if(is_null($_fp))
  {
    date_default_timezone_set(timezone() ?? 'UTF8');

    $logfile = app_file(log_file());
    if( file_exists($logfile) and filesize($logfile) > 1024*1024 ) {
      $tempfile = $logfile.".tmp";
      $_fp = fopen($tempfile,"w");
      $skip = 1000;
      foreach(file($logfile) as $line) {
        if($skip > 0) {
          $skip--;
        } else {
          fwrite($_fp,$line);
        }
      }
      fclose($_fp);
      unlink($logfile);
      rename($tempfile,$logfile);
    }
    $_fp = fopen($logfile,"a");
  }
  return $_fp;
}

function log_array($x)
{
  return json_encode($x);
}

// writes to the app log based on prefix and log level
//   unrecognized prefixes will be ignored, but...
//     if the log level includes warnings, a warning will be
//     added to the log about the unknown prefix
function write_to_logger($prefix,$msg,$trace_level=1)
{
  $prefix = strtoupper($prefix);
  switch($prefix)
  {
  case "ERROR":
    $prefix = "ERR";
  case "ERR":     
    $level = 0; 
    break;

  case "WARNING":
    $prefix = "WARN";
  case "WARN":    
    $level = 1; 
    break;

  case "TODO":
  case "INFO":    
    $level = 2; 
    break;

  case "DEV":     
    $level = 3; 
    break;

  default:        
    log_warning("Invalid logging prefix: $prefix"); 
    break;
  }

  if( $level <= log_level() ) 
  {
    $timestamp = date("d-M-y H:i:s T");

    if($trace_level>0 && ! preg_match('/Exception\s+\d+\s+caught/',$msg) ) {
      $trace = debug_backtrace();
      $file = $trace[$trace_level]["file"] || "???";
      $line = $trace[$trace_level]["line"] || "???";
      if(str_starts_with($file,APP_DIR)) {
        $file = substr($file,1+strlen(APP_DIR));
      }
      $msg .= " [$file:$line]";
    }

    $prefix = str_pad($prefix,8);
    fwrite(logger(), "[$timestamp] {$prefix} $msg\n");
  }
}

function log_location()
{
  $trace = debug_backtrace();
  $pre_re = '/^'.APP_DIR.'\//';
  $file = $trace[1]["file"];
  $line = $trace[1]["line"];
  if(str_starts_with($file,APP_DIR)) {
    $file = substr($file,1+strlen(APP_DIR));
  }
  return "$file[$line]";
}

// the todo function is both a way to mark the code and to include
//   those todos in the log file at the INFO level
function todo($msg,$trace=1) {
  write_to_logger("TODO",$msg,$trace);
}

// log_dev is intended to only be useful during development debugging
function log_dev($msg,$trace=1) {
  write_to_logger("DEV",$msg,$trace);
}

// log_info is intended to show normal flow through the plugin code
function log_info($msg,$trace=1) {
  write_to_logger("INFO",$msg,$trace);
}

// log_warning is intended to show abnormal, but not necessarily
//   critical flows through the plugin code
function log_warning($msg,$trace=1) {
  write_to_logger("WARNING",$msg,$trace);
}

// log_error is intended to show critical errors in the plugin code
function log_error($msg,$trace=1) {
  write_to_logger("ERROR",$msg,$trace);
  error_log(PKG_NAME.": $msg");
}

function warning_handler($errno,$errstr,$errfile,$errline)
{

  if(str_starts_with($errfile,APP_DIR)) {
    $errfile = substr($errfile,1+strlen(APP_DIR));
  }
  log_warning("$errstr [$errfile:$errline]",0);
  return true;
}

function handle_warnings() 
{
  set_error_handler('tlc\tts\warning_handler',E_WARNING|E_NOTICE);
}
