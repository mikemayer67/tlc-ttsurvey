<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;

function logTrace() : string
{
  $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

  // skipping this function, crawl up the stack to find the first
  // frame which is a non-excluded function
  for($i=1; $i<count($trace); ++$i) {
    if(includeFrameInLogTrace($trace[$i])) {
      break;
    }
  }
  // We got here either because we found a frame to include and broke out of the
  //   preceding for loop or because we exhasted all of the frames on the call stack
  //
  // In the first case, the file/line numbers are where this frame was called from.
  //   But we want to know where in this frame the logging was called from.
  //   We need to back up one level to find this info.
  //
  // In the second case, we need to back up one level to get back into range.
  //   And that just so happens to give us the info we want
  $file = $trace[$i-1]["file"] ?? "???";
  $line = $trace[$i-1]["line"] ?? "???";

  $file = str_starts_with($file, APP_DIR)
        ? substr($file, 1 + strlen(APP_DIR))
        : $file;
        
  return "[$file:$line]";
}

/**
 * Determines whether or not a given frame in the trace stack should be included in
 *   logging, i.e. if the frame's class, method, or function has not been marked with 
 *   the ExcludeFromLogTrace attribute.
 * @param array{class?: string, function?:string} $frame 
 * @return bool 
 */
function includeFrameInLogTrace(array $frame): bool
{
  $class    = $frame['class']    ?? '';
  $function = $frame['function'] ?? '';

  try {
    if($class) {
      // see if the entire class is excluded from the log trace
      if( excludeClassFromLogTrace($class) ) { return false; }
      // if not, see if the specific class method is excluded
      if($function) {
        return includeMethodInLogTrace($class,$function);
      }
    } elseif($function) {
      // see if the function is excluded from the log trace
      return includeFunctionInLogTrace($function);
    }
    // nothing about this frame indicates exclusion from the log trace
    return false;
  } 
  catch(ReflectionException $e) {
    return false;
  }
}

/**
 * Determines whether a given class should be excluded the log trace
 * @param string $class 
 * @return bool
 */
function excludeClassFromLogTrace(string $class) : bool
{
  static $exclude = [];

  // if this is the first time we're looking at this class, perform reflection
  if(!isset($exclude[$class])) {
    $rc = new ReflectionClass($class);
    $exclude[$class] = !empty($rc->getAttributes(ExcludeFromLogTrace::class));
  }

  return $exclude[$class];
}

/**
 * Determine whether the specified class method should be included in the log trace
 * @param string $class
 * @param string $method 
 * @return bool 
 */
function includeMethodInLogTrace(string $class, string $method) : bool
{
  static $include = [];

  // if this is the first time we're looking at this method, perform relection
  $key = $class. '::' . $method;
  if(!isset($include[$key])) {
    $rc = new ReflectionClass($class);
    $rm = $rc->getMethod($method);
    $include[$key] = empty($rm->getAttributes(ExcludeFromLogTrace::class));
  }
  return $include[$key];
}

/**
 * Determine whether the specified function should be included in the log trace
 * @param string $function
 * @return bool 
 */
function includeFunctionInLogTrace(string $function) : bool
{
  // Seed the include cache with language constructs that appear as "functions" 
  //   in backtraces but are not reflectable
  static $include = [
    'require' => true,
    'require_once' => true,
    'include' => true,
    'include_once' => true,
  ];

  // if this is the first time we're looking at this function, perform relection
  if(!isset($include[$function])) {
    $rf = new ReflectionFunction($function);
    $include[$function] = empty($rf->getAttributes(ExcludeFromLogTrace::class));
  }
  return $include[$function];
}