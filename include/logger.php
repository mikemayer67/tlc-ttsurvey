<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/const.php');
require_once app_file('include/settings.php');

$_logger_fp = null;

date_default_timezone_set(get_setting(TIMEZONE_KEY));


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


function write_to_logger($prefix,$msg)
{
  $timestamp = date("d-M-y H:i:s.v T");
  $prefix = str_pad($prefix,8);
  fwrite(logger(), "[{$timestamp}] {$prefix} {$msg}\n");
}


function get_log_level()
{
  return get_setting(LOG_LEVEL_KEY);
}

function set_log_level($level)
{
  update_setting(LOG_LEVEL_KEY,$level);
}


/**
 * log_dev is intended to only be useful during development debugging
 */
function log_dev($msg) {
  if(get_log_level() >= LOGGER_DEV) {
    write_to_logger(LOGGER_PREFIX[LOGGER_DEV],$msg);
  }
}

/**
 * log_info is intended to show normal flow through the plugin code
 **/
function log_info($msg) {
  if(get_log_level() >= LOGGER_INFO) {
    write_to_logger(LOGGER_PREFIX[LOGGER_INFO],$msg);
  }
}

/**
 * log_warning is intended to show abnormal, but not necessarily
 *   critical flows through the plugin code
 */
function log_warning($msg) {
  if(get_log_level() >= LOGGER_WARN) {
    write_to_logger(LOGGER_PREFIX[LOGGER_WARN],$msg);
  }
}

/**
 * log_error is intended to show critical errors in the plugin code
 **/
function log_error($msg) {
  write_to_logger(LOGGER_PREFIX[LOGGER_ERR],$msg);
  error_log(APP_NAME.": $msg");
}
