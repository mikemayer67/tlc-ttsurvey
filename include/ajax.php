<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

/**
 * Packages a response from AJAX call and sends it to the browser
 * @param array $data will be json encoded in response to browser
 * @param int $http_code 
 * @param bool $die function ends with a call to die unless set to false
 * @return void
 */
function send_ajax_response(array $data, int $http_code=200, bool $die=true)
{
  http_response_code($http_code);
  header('Content-Type: application/json; charset=utf-8');
  $rval = json_encode($data);
  echo $rval;

  if($die) { die(); }
}

/**
 * Packages a response to a valid request that failed for some valid reason
 * The response will contain only two keys:
 *   success: false
 *   reason:  the provided error message
 * @param string $message 
 * @param bool $die function ends with a call to die unless set to false
 * @return void 
 */
function send_ajax_failure(string $message, bool $die=true)
{
  send_ajax_response(['success'=>false, 'reason'=>$message], 200, $die);
}

/**
 * Returns a 400 (BAD_REQUEST) error on an AJAX call
 *   This indicates incorrect usage of the API
 * @param string $error 
 * @return void 
 */
function send_ajax_bad_request(string $error)
{
  require_once('include/logger.php');
  $errid = bin2hex(random_bytes(3));
  log_error("[$errid]BAD_REQUEST: $error",2);
  send_ajax_response(['reason'=>$error],400);
}

/**
 * Returns a 401 (UNAUTHORIZED) error on an AJAX call
 *   This indicates missing/expired login credentials
 * @param string $error 
 * @return void 
 */
function send_ajax_unauthorized(string $error)
{
  require_once('include/logger.php');
  $errid = bin2hex(random_bytes(3));
  log_error("[$errid]UNAUTHORIZED: $error",2);
  send_ajax_response(['reason'=>$error],401);
}

/**
 * Returns a 403 (FORBIDDEN) error on an AJAX call
 *   For the survey app, this means that a bad/expired nonce was encountered
 * @param string $error 
 * @param int $level trace level for logging (2 logs immediate caller)
 * @return void 
 */
function send_ajax_bad_nonce(string $error, int $level=2)
{
  require_once('include/logger.php');
  require_once('include/login.php');
  $userid = active_userid();
  log_warning("Bad nonce [$userid]: $error",$level);
  send_ajax_response(['reason'=>"Invalid nonce"],403);
}

/**
 * Siminar to the internal_error function, but intended for use from within AJAX calls
 * @param string $msg 
 * @return void 
 */
function send_ajax_internal_error(string $error)
{
  require_once('include/logger.php');
  $errid = bin2hex(random_bytes(3));
  log_error("[$errid]: $error",2);
  send_ajax_response(['reason'=>$error],500);
}

/**
 * Compares the nonce to the current session stored value.
 *   Immediately sends a 403 response to the AJAX call on failure
 * @param string $key 
 * @return void 
 */
function validate_ajax_nonce(string $key)
{
  $expected = $_SESSION['nonce'][$key] ?? null;
  $actual   = $_POST['nonce'];
  if($actual !== $expected) {
    // pass trace level of 3 (log caller of this function, not this function)
    send_ajax_bad_nonce("expected=$expected, actual=$actual",3);
  }
}

/**
 * Extracts and validates integer input to an AJAX call
 *   Sends an immediate ajax failure if validation does not pass
 * @param string $key 
 * @param null|int $default (optional) if not provided, input value is required
 * @param null|int $min (optional)
 * @param null|int $max (optional)
 * @return int 
 */
function parse_ajax_integer_input(string $key,?int $default=null, ?int $min=null, ?int $max=null) : int
{
  $value = $_POST[$key] ?? $default;
  if(is_null($value)) { send_ajax_bad_request("requred $key input is missing"); }
  $value = filter_var($value, FILTER_VALIDATE_INT);
  if($value === false) { send_ajax_bad_request("$key input must be an integer"); }
  if(!is_null($min) && $value < $min ) { send_ajax_bad_request("$key input must be at least $min"); }
  if(!is_null($max) && $value > $max ) { send_ajax_bad_request("$key input must be at most $max"); }
  return $value;
}

/**
 * Extracts and validates string input to an AJAX call
 *   Sends an immediate ajax failure if validation does not pass
 *   Removes quoting and leading/trailing whitespace
 * @param string $key 
 * @param string|null $default (optional) if not provided, input value is required
 * @return string 
 */
function parse_ajax_string_input(string $key,?string $default=null) : string
{
  $value = $_POST[$key] ?? $default;
  if(is_null($value)) { send_ajax_bad_request("requred $key input is missing"); }
  $value = trim(stripslashes($value));
  return $value;
}

class AjaxResponse
{
  private bool  $_success;
  private array $_data;
  
  /**
   * Constructs a new AjaxResponse, assuming success unless otherwise indicated
   * @param bool $success 
   * @return void 
   */
  public function __construct(bool $success=true)
  {
    $this->_success = $success;
    $this->_data = [];
  }
  /**
   * Set the ajax response to indicate success in performing the desired action
   * @param string $message 
   * @return void 
   */
  public function succeed(string $message='') { 
    $this->_success = true;
    if($message) {
      $this->_data['message'] = $message;
    }
  }
  /**
   * Set the ajax response to indicate failure to perform the desired action
   *   Do not confuse this with a failure to authenticate or improper usage of the API
   * @param string $reason 
   * @return void 
   */
  public function fail(string $reason='') { 
    $this->_success = false; 
    if($reason) {
      $this->_data['reason'] = $reason;
    }
  }

  /**
   * Adds a data parameter to the ajax response
   * @param string $key 
   * @param mixed $value 
   * @return void 
   */
  public function add(string $key, mixed $value)
  {
    $this->_data[$key] = $value;
  }

  /**
   * Sends the ajax response to the browser after adding success status to the data
   * @return void 
   */
  public function send()
  {
    $this->_data['success'] = $this->_success;
    send_ajax_response($this->_data);
  }
}