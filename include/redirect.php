<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

define('REDIRECT_TIMEOUT', 10); // seconds
define('REDIRECT_KEY', 'tlc.tts.redirect');

// The classes and functions in this file allow for data to be retained across a
//    http redirection back to the app's main entry point.  
//
// As there is no mechanism for sending POST data with the redirection, this
//   data is stored in the SESSION data.  There are two pieces to this data:
//     - page: (string) contains the page/action to be resumed after the redirect
//     - data: (array) auxilary data needed to resume the page/action after the redirect 
//   The actual usage of this data is determined as needed.
//
// Because this is meant to be a sort of "jump cable" across the redirect, this
//   data should not persist to subsequent app entries.  Once the data has been
//   retrieved, it is immediately purged from the SESSION data.

/**
 * Writer class for redirect session data.
 * Stores values directly in $_SESSION; does not persist in object instance.
 */
class RedirectDataWriter 
{
  /**
   * Starts (or replaces) the redirect data in the session data
   * @param string $page 
   * @return void 
   */
  public function __construct(string $page)
  {
    $_SESSION[REDIRECT_KEY] = [
      'page' => $page,
      'data' => [],
      'expires' => time() + REDIRECT_TIMEOUT, // data expires 10 seconds after this method is called
    ];
  }

  /**
   * Appends a key/value entry to the redirect data
   *   returns a pointer to the class instance for to enable 
   *   chaining of add() calls.
   * @param string $key 
   * @param mixed $value 
   * @return RedirectDataWriter
   */
  public function add(string $key, mixed $value) : RedirectDataWriter
  {
    if (!isset($_SESSION[REDIRECT_KEY])) {
      internal_error("SESSION redirect data was prematurely purged");
    }
    $_SESSION[REDIRECT_KEY]['data'][$key] = $value;

    return $this;
  }
}

/**
 * Handles the reading of redirect data in the session data
 *   This is a singleton class that must be accessed using the 
 *   instance() class method.
 */
class RedirectDataReader
{
  private static $instance_ = null;
  private $page = null;
  private $data = null;

  public static function instance() : RedirectDataReader
  {
    if(!self::$instance_) { self::$instance_ = new RedirectDataReader(); }
    return self::$instance_;
  }

  private function __construct()
  {
    if (!isset($_SESSION[REDIRECT_KEY])) { return; }
    // purge the data if we're after the expiration time
    $expires = $_SESSION[REDIRECT_KEY]['expires'] ?? 0;
    if( time() <= $expires) {
      $this->page = $_SESSION[REDIRECT_KEY]['page'] ?? null;
      $this->data = $_SESSION[REDIRECT_KEY]['data'] ?? [];
    }

    unset($_SESSION[REDIRECT_KEY]);
  }

  /**
   * returns the redirect page (if set) or null (if not)
   * @return null|string 
   */
  public function page() : ?string { return $this->page; }

  /**
   * returns any defined redirect data 
   * @return null|array 
   */
  public function data() : ?array  { return $this->data; }
}

/**
 * Purges redirect data defined in the session data
 * @return void 
 */
function clear_redirect()
{
  unset($_SESSION[REDIRECT_KEY]);
}

/**
 * Constructs and returns a new RedirectDataWriter
 * @param string $page 
 * @return RedirectDataWriter 
 */
function start_redirect(string $page) : RedirectDataWriter
{
  return new RedirectDataWriter($page);
}

/**
 * returns the redirect page (if set) or null (if not)
 * It is intended that this function will be called when determining 
 *   the survey or admin page to redirect to.  
 * @return null|string 
 */
function get_redirect_page() : ?string
{
  $reader = RedirectDataReader::instance();
  return $reader->page();
}

/**
 * returns any redirect data defined in the session data
 * @return null|array
 */
function get_redirect_data() : ?array
{
  $reader = RedirectDataReader::instance();
  return $reader->data();
}