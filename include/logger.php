<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/const.php');
require_once app_file('include/settings.php');

date_default_timezone_set(LOGGER_TZ); // from config file via const.php

$_logger_fp = null;

function logger($create=true)
{
  global $_logger_fp;

  if(is_null($_logger_fp) && $create)
  {
    $logfile = app_file(LOGGER_FILE);
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

function clear_logger()
{
  global $_logger_fp;

  $file = app_file(LOGGER_FILE);
  if($_logger_fp) { fclose($_logger_fp); }
  unlink($file);
  $_logger_fp = fopen($file,"a");
  log_info("log cleared");
}

// writes to the app log based on prefix and log level
//   unrecognized prefixes will be ignored, but...
//     if the log level includes warnings, a warning will be
//     added to the log about the unknown prefix
function write_to_logger($prefix,$msg)
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
    $prefix = str_pad($prefix,8);
    fwrite(logger(), "[{$timestamp}] {$prefix} {$msg}\n");
  }
}


// accessors to the current log level in the admin settings
function get_log_level()       { return get_setting(LOG_LEVEL_KEY);    }
function set_log_level($level) { update_setting(LOG_LEVEL_KEY,$level); }

// the todo function is both a way to mark the code and to include
//   those todos in the log file at the INFO level
function todo($msg) {
  $trace = debug_backtrace();
  $pre_re = '/^'.APP_DIR.'\//';
  $file = $trace[0]["file"];
  $line = $trace[0]["line"];
  if(str_starts_with($file,APP_DIR)) {
    $file = substr($file,1+strlen(APP_DIR));
  }
  $msg = $file . "[" . $line . "]: " . $msg;
  write_to_logger("TODO",$msg);
}

// log_dev is intended to only be useful during development debugging
function log_dev($msg) {
  write_to_logger("DEV",$msg);
}

// log_info is intended to show normal flow through the plugin code
function log_info($msg) {
  write_to_logger("INFO",$msg);
}

// log_warning is intended to show abnormal, but not necessarily
//   critical flows through the plugin code
function log_warning($msg) {
  write_to_logger("WARNING",$msg);
}

// log_error is intended to show critical errors in the plugin code
function log_error($msg) {
  write_to_logger("ERROR",$msg);
  error_log(APP_NAME.": $msg");
}
