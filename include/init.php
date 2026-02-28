<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

define('APP_URI', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/');
define('PKG_NAME', 'tlc-ttsurvey');

// Error handling 

class BadInput      extends \Exception {}

/**
 * Adds the API usage error (with optional message) to the error log 
 *   and terminates PHP immediately
 * @param string $msg 
 * @return never 
 */
function api_die(string $msg='') 
{
  error_log("API Error[$msg]: ".print_r($_SERVER,true));
  http_response_code(405);
  require(APP_DIR."/405.php");
  die; 
}

/**
 * Adds the internal error message to the error log, 
 *   returns the http 500 error status and splash screen,
 *   and then terminates PHP
 * @param string $msg 
 * @return void 
 */
function internal_error(string $msg)
{
  // avoid recursion if internal error occurred while rendering 500.php
  if(defined('RENDERING_ERR_PHP')) { return; }

  require_once('include/logger.php');
  $errid = bin2hex(random_bytes(3));
  log_error("[$errid]: $msg",2);
  http_response_code(500);
  require(app_file("500.php"));
  die;
}

/**
 * Adds the input validation error message to the warning log, 
 *   returns the http 405 error status and splash screen,
 *   and then terminates PHP
 * @param string $msg 
 * @return void 
 */
function validation_error(string $msg)
{
  // avoid recursion if internal error occurred while rendering 500.php
  if(defined('RENDERING_ERR_PHP')) { return; }

  require_once('include/logger.php');
  $errid = bin2hex(random_bytes(3));
  log_warning("[$errid]: $msg",2);
  http_response_code(405);
  require(app_file("405.php"));
  die;
}

/**
 * Constructs the fully qualified filesystem path name
 * @param string $path relative to the app's root directory
 * @return string 
 */
function app_file(string $path) : string { return APP_DIR . "/$path"; }

/**
 * Returns the URI relative to the app's primary API entry point 
 * @param null|string $q optional URI query parameters
 * @return string 
 */
function app_uri(?string $q=null) : string  { return ($q ? "tt.php?$q" : "tt.php"); }

function app_repo() {
  static $repo_url = null;
  if(is_null($repo_url)) {
    $git_config_file = app_file('.git/config');
    if (is_readable($git_config_file)) {
      $git_config = parse_ini_file($git_config_file);
      $repo_url = $git_config['url'] ?? null;
      if ($repo_url) {
        $repo_url = str_replace('git@github.com:', 'https://github.com/', $repo_url);
        $repo_url = str_replace('.git', '', $repo_url);
      }
    }
  }
  return $repo_url;
}

/**
 * Constructs a fully qualified VALIDATED filesytem path name
 *   Should be used instead of app_file whenver $path is tainted
 *   Enusres the requested path is within the app's root directory
 * If an invalid request is received, the request is added to the error log,
 *   an HTTP 405 is returned, and PHP is terminated.
 * @param string $path 
 * @return string 
 */
function safe_app_file(string $path) : string { 
  $file = realpath(app_file($path));
  if($file && str_starts_with($file,APP_DIR)) {
    return $file;
  } else {
    http_response_code(405);
    error_log("Unsafe app file requested: $path");
    die();
  }
}

/**
 * Returns the full URI for the app's primary API entry point 
 * @param null|string $q optional URI query parameters
 * @return string 
 */
function full_app_uri(?string $q=null) {
  $scheme = 'https';
  if(($_SERVER['HTTPS'] ?? 'off')==='off') { $scheme = 'http'; }
  $host = $_SERVER['HTTP_HOST'];
  $path = $_SERVER['SCRIPT_NAME'];
  $rval = "$scheme://$host$path";
  if($q) { $rval .= "?$q"; }
  return $rval;
}

// in order to prevent resource files from caching, we append a changing query
// string to the URL for the css file... but this should only be needed
// in a development environment.

/**
 * Constructs the URI for a requested resource relative to the root URI
 *   [{context}/]{type}/{rsrc}[?v={rand()}]
 * @param string $rsrc resource filename 
 * @param string $type type of resource (i.e. name of subdirectory)
 * @param bool $no_cache prevents caching in dev environaments
 * @param string $context context based subdirectory of the app's root directory
 * @return string 
 */
function rsrc_uri(string $rsrc,string $type,bool $no_cache,string $context='') :string  {
  $rsrc = trim($rsrc," /");
  $type = trim($type," /");
  $context = trim($context," /");
  $uri = $context ? "$context/" : "";
  $uri .= "$type/$rsrc";
  if($no_cache && is_dev()) { $uri .= '?v=' . rand(); }
  return $uri;
}

/**
 * Constructs the URI for a requested image file relative to the root URI
 *   [{context}/]img/{rsrc}[?v={rand()}]
 * Caching is disabled in dev environements
 * @param string $img image filename 
 * @param string $context context based subdirectory of the app's root directory
 * @return string 
 */
function img_uri(string $img,string $context='') :string 
{ 
  return rsrc_uri( $img, 'img',true, $context);
}

/**
 * Constructs the URI for a requested css file relative to the root URI
 *   [{context}/]css/{css}.css[?v={rand()}]
 * Caching is disabled in dev environements
 * @param string $css base of the css filename (without .css extension)
 * @param string $context context based subdirectory of the app's root directory
 * @return string 
 */
function css_uri(string $css,string $context='') : string 
{ 
  return rsrc_uri("$css.css",'css',true, $context);
}

/**
 * Constructs the URI for a requested javascript file relative to the root URI
 *   [{context}/]js/{js}.js[?v={rand()}]
 * Caching is not disabled as this clears breakpoints
 * @param string $js base of the javascript filename (without .js extension)
 * @param string $context context based subdirectory of the app's root directory
 * @return string 
 */
function js_uri(string $js, string $context='') 
{ 
  return rsrc_uri("$js.js",  'js', false, $context);
}

/**
 * Returns best guess if we are in a Safari environment
 * @return bool 
 */
function is_safari() : bool {
  // detects if we're on a safari browser
  $ua = $_SERVER['HTTP_USER_AGENT'];
  // Match Safari on macOS or iOS
  // Exclude Chrome/CriOS, Chromium, Edge (WebKit-based)
  return preg_match('/Safari/i', $ua)
    && !preg_match('/Chrome|CriOS|Chromium|Edg/i', $ua);
}

/**
 * Invokes a context specific function variant (if it exists)
 *   tlc\tts\{base_name}_{contenxt}(...{args})
 * @param string $base_name name of the function within the context
 * @param string $context name of context
 * @param mixed ...$args passed to the context based function
 * @return mixed retuned from the context based function
 */
function call_context_function(string $base_name,string $context,mixed ...$args) : mixed
{
  $base_name = 'tlc\\tts\\' . $base_name;
  $context_name = implode('_',[$base_name,$context]);
  $function_name = function_exists($context_name) ? $context_name : $base_name;

  return $function_name(...$args);
}

/**
 * Validates that the requested URI doesn't validate the app's API
 *   If it does, app_die is invoked with the violation.
 *   app_die will log the error, set the HTTP response code,  and terminate PHP
 * @return void 
 */
function validate_entry_uri()
{
  // Validate the request URI matches our API
  // http[s]://(host[/dir])/[tt|tt.php][?query]
  $request_uri = $_SERVER['REQUEST_URI'];
  // Needs to start with the URI for our app
  if(!str_starts_with($request_uri,APP_URI)) { api_die("Bad URI: '$request_uri'"); }
  // Good... now strip that off the request (along with the / that follows)
  $request_uri = substr($request_uri,strlen(APP_URI));
  // Strip off any query string
  $pos = strpos($request_uri,"?");
  if($pos !== false ) { $request_uri = substr($request_uri,0,$pos); }
  // All we should be left with is an allowable entry point (e.g. tt.php, 405.php, 500.php)
  $allowable = ["", "tt","tt.php","405.php","500.php","admin/","admin.php","preview/"];
  if(!in_array($request_uri,$allowable) ) { api_die("Invalid resource: '$request_uri'"); }
  // We're good!
}

/**
 * Generates an alphanumeric token string of specified length
 * @param int $token_length 
 * @return string 
 */
function gen_token(int $token_length=25) : string
{
  $token = '';
  $token_pool = '123456789123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  for($i=0; $i<$token_length; $i++) {
    $index = rand(0,strlen($token_pool)-1);
    $token .= $token_pool[$index];
  }
  return $token;
}

/**
 * Generates and records a new nonce string
 * @param string $key used to identify the nonce usage
 * @return string  16 character token
 */
function gen_nonce(string $key) : string
{
  $nonce = gen_token(16);
  $_SESSION['nonce'][$key] = $nonce;
  return $nonce;
}

/**
 * Returns the nonce asociated with the specified string
 * @param string $key used to identify the nonce usage
 * @return null|string 16 character token
 */
function get_nonce(string $key) : ?string
{
  return $_SESSION['nonce'][$key] ?? null;
}

/**
 * Validates a nonce found in the POST or GET query
 *   If this is a single use nonce, it will be removed from the SESSION cache
 *   If this is a multi use nonce, it will be retained in the SESSION cache
 * If the nonce fails to validate, api_die will be invoked, terminating PHP
 * @param string $key used to identify the nonce usage
 * @param string $src either 'POST' or 'GET'
 * @param bool $invalidate true:forget the nonce, false:retain the nonce
 * @return void 
 */
function validate_nonce(string $key,string $src='POST',bool $invalidate=true)
{
  $expected = $_SESSION['nonce'][$key] ?? null;
  $actual = (strtolower($src)==='get') ? ($_GET['ttt'] ?? null) : ($_POST['nonce'] ?? null);
  if($actual !== $expected) {
    log_warning("Invalid nonce: ($key:$actual/$expected)",2);
    api_die("Invalid nonce: key=$key");
  }
  if($invalidate) { $_SESSION['nonce'][$key] = null; }
}

/**
 * Validates a nonce found in a GET query
 *   If this is a single use nonce, it will be removed from the SESSION cache
 *   If this is a multi use nonce, it will be retained in the SESSION cache
 * If the nonce fails to validate, api_die will be invoked, terminating PHP
 * @param string $key used to identify the nonce usage
 * @param bool $invalidate true:forget the nonce, false:retain the nonce
 * @return void 
 */
function validate_get_nonce(string $key,bool $invalidate=true)
{ 
  validate_nonce($key,'GET',$invalidate);
}

/**
 * Validates a nonce found in the POST or GET query
 * The nonce is retained inthe SESSION cache.
 * If the nonce fails to validate, api_die will be invoked, terminating PHP
 * @param string $key used to identify the nonce usage
 * @param string $src either 'POST' or 'GET'
 * @param string $key 
 * @param string $src 
 * @return void 
 */
function validate_and_retain_nonce(string $key,string $src='POST') 
{ 
  validate_nonce($key,$src,false);
}

/**
 * Validates a nonce found in a GET query. 
 * The nonce is retained inthe SESSION cache.
 * If the nonce fails to validate, api_die will be invoked, terminating PHP
 * @param string $key used to identify the nonce usage
 * @return void 
 */
function validate_and_retain_get_nonce(string $key)
{ 
  validate_nonce($key,'GET',false);
}

// We don't want to get past inclusion of init.php if the URI violates the app URI
validate_entry_uri();
