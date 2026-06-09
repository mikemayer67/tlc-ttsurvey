<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/logger_trace.php');
require_once app_file('include/settings.php');

/**
 * returns the path to the survey app log file
 * @return string 
 */
function log_file() : string
{ 
  static $log_file = null;
  if(!$log_file) {
    $config = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true);
    $log_file = $config['log_file'] ?? PKG_NAME.'.log';
  }
  return $log_file;
}

/**
 * returns opened file pointer to the survey app log
 * @return resource|false 
 */
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

/**
 * Conditionally writes to the app log based on prefix and log level
 * @param string $prefix 
 * @param string $msg 
 * @param bool $includeTrace
 * @return void 
 */
#[ExcludeFromLogTrace]
function write_to_logger(string $prefix,string $msg,bool $includeTrace=true)
{
  $prefix = strtoupper($prefix);
  switch($prefix)
  {
  case "ERROR":   $level = 0; break;
  case "WARNING": $level = 1; break;
  case "TODO":
  case "INFO":    $level = 2; break;
  case "DEV":     $level = 3; break;
  default:        
    log_warning("Invalid logging prefix: $prefix"); 
    $level = 0;
    break;
  }

  if( $level <= log_level() ) 
  {
    $timestamp = date("d-M-y H:i:s T");

    $trace = '';
    if($includeTrace && ! preg_match('/Exception\s+\d+\s+caught/',$msg) ) {
      $trace = logTrace();
    }

    $prefix = str_pad($prefix,8);
    fwrite(logger(), "[$timestamp] {$prefix} $msg $trace\n");

    // also write ERROR level messages into the php error log
    if($level === 0) {
       error_log(PKG_NAME.": $msg $trace");
    }
  }
}

/**
 * Includes a TODO entry in the log file (at the INFO level)
 * @param string $msg 
 * @return void 
 * @note this function serves as a good way to mark todos in code
 */
#[ExcludeFromLogTrace]
function todo(string $msg) {
  write_to_logger("TODO",$msg);
}

/**
 * Adds a DEV level entry in the log file
 *   Intended to only be useful during development/debugging
 * @param mixed $msg 
 * @return void 
 * @note only adds entry if current logging level is development
 */
#[ExcludeFromLogTrace]
function log_dev(string $msg) { write_to_logger("DEV",$msg); }

/**
 * Adds a INFO level entry in the log file
 *   Intended to show normal flow through the survey app
 * @param string $msg 
 * @return void 
 * @note only adds entry if current logging level is info or dev
 */
#[ExcludeFromLogTrace]
function log_info(string $msg) { write_to_logger("INFO",$msg); }

/**
 * Adds a WARNING level entry in the log file
 *   Intended to show abnormal behavior, but not necessary critical errors
 * @param string $msg 
 * @return void 
 * @note only adds entry if current logging level is warning, info, or dev
 */
#[ExcludeFromLogTrace]
function log_warning(string $msg) { write_to_logger("WARNING",$msg); }

/**
 * Adds an ERROR level entry in the log file
 *   Intended to show critical errors
 * @param string $msg 
 * @return void 
 * @note error level entries are always written to the log
 */
#[ExcludeFromLogTrace]
function log_error(string $msg) 
{
  write_to_logger("ERROR",$msg);
}

/**
 * Sets up an error handler to write warnings and notices to the log
 *   rather than standard out (where they end up in the DOM).
 * It handles:
 *   - E_WARNING
 *   - E_NOTICE
 *   - E_DEPRECATED
 *   - E_USER_DEPRECATED
 * @return void 
 */
function handle_warnings() 
{
  set_error_handler(
    function($errno, $errstr, $errfile, $errline) {
      if (str_starts_with($errfile, APP_DIR)) {
        $errfile = substr($errfile, 1 + strlen(APP_DIR));
      }
      write_to_logger('WARNING', "$errstr [$errfile:$errline]", includeTrace:false);
      return true;
    },
    E_WARNING|E_NOTICE|E_DEPRECATED|E_USER_DEPRECATED
  );
}

/**
 * Sets up output buffering of unhandled warnings
 * @return void 
 */
function start_ob_logging()
{
  handle_warnings();
  ob_start();
}

/**
 * Ends output buffering of unhandled warnings and logs any caught warnings.
 * @return void 
 */
function end_ob_logging()
{
  $warning = ob_get_contents();
  if($warning) { log_warning("OB Warning: $warning"); }
  ob_end_clean();
}
