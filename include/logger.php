<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/const.php');
require_once app_file('include/settings.php');

date_default_timezone_set(APP_TZ); // from config file via const.php

$_logger_fp = null;

function logger($create=true)
{
  global $_logger_fp;

  if(is_null($_logger_fp) && $create)
  {
    $logfile = app_file(LOG_FILE);
    if( file_exists($logfile) and filesize($logfile) > 512*1024 ) {
      $tempfile = $logfile.".tmp";
      $_logger_fp = fopen($tempfile,"w");
      $skip = 1000;
      foreach(file($logfile) as $line) {
        if($skip > 0) {
          $skip--;
        } else {
          fwrite($_logger_fp,$line);
        }
      }
      fclose($_logger_fp);
      unlink($logfile);
      rename($tempfile,$logfile);
    }
    $_logger_fp = fopen($logfile,"a");
  }
  return $_logger_fp;
}

function log_array($x)
{
  return json_encode($x);

}

function clear_logger()
{
  global $_logger_fp;

  $file = app_file(LOG_FILE);
  if($_logger_fp) { fclose($_logger_fp); }
  unlink($file);
  $_logger_fp = fopen($file,"a");
  log_info("log cleared");
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

  if( $level <= get_log_level() ) 
  {
    $timestamp = date("d-M-y H:i:s.v T");

    if(! preg_match('/Exception\s+\d+\s+caught/',$msg) ) {
      $trace = debug_backtrace();
      $file = $trace[$trace_level]["file"];
      $line = $trace[$trace_level]["line"];
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



// accessors to the current log level in the admin settings
function get_log_level()       { return get_setting(LOG_LEVEL_KEY);    }
function set_log_level($level) { update_setting(LOG_LEVEL_KEY,$level); }

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
