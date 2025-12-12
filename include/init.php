<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

define('APP_URI', preg_replace("/\/[^\/]+$/","",$_SERVER['SCRIPT_NAME']));
define('PKG_NAME', 'tlc-ttsurvey');

// Error handling 

class BadInput      extends \Exception {}
class MissingInput  extends \Exception {}
class SMTPError     extends \Exception {}

function api_die() 
{
  error_log("API Error: ".print_r($_SERVER,true));
  http_response_code(405);
  require(APP_DIR."/405.php");
  die; 
}

function internal_error($msg)
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

function validation_error($msg)
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

// in order to prevent css files from caching, we append a changing query
// string to the URL for the css file... but this should only be needed
// in a development environment.

function base_uri() { return str_ends_with(APP_URI,"/") ? APP_URI : APP_URI.'/'; }

function app_file($path)   { return APP_DIR . "/$path"; }
function app_uri($q=null)  { return ($q ? "tt.php?$q" : "tt.php"); }

function safe_app_file($path) { 
  // should be used instead of app_file whenever $path is tainted
  $file = realpath(app_file($path));
  if($file && str_starts_with($file,APP_DIR)) {
    return $file;
  } else {
    http_response_code(405);
    error_log("Unsafe app file requested: $path");
    die();
  }
}

function full_app_uri($q=null) {
  $scheme = 'https';
  if(($_SERVER['HTTPS'] ?? 'off')==='off') { $scheme = 'http'; }
  $host = $_SERVER['HTTP_HOST'];
  $path = $_SERVER['SCRIPT_NAME'];
  $rval = "$scheme://$host$path";
  if($q) { $rval .= "?$q"; }
  return $rval;
}

function rsrc_uri($rsrc,$type,$no_cache,$context='') {
  $rsrc = trim($rsrc," /");
  $type = trim($type," /");
  $context = trim($context," /");
  $uri = $context ? "$context/" : "";
  $uri .= "$type/$rsrc";
  if($no_cache && is_dev()) { $uri .= '?v=' . rand(); }
  return $uri;
}

function img_uri($img,$ctx='') { return rsrc_uri( $img,     'img',true, $ctx); }
function css_uri($css,$ctx='') { return rsrc_uri("$css.css",'css',true, $ctx); }
// caching is not disabled for js as forced reload clears breakpoints
function  js_uri($js, $ctx='') { return rsrc_uri("$js.js",  'js', false, $ctx); }


function is_safari() {
  // detects if we're on a safari browser
  $ua = $_SERVER['HTTP_USER_AGENT'];
  // Match Safari on macOS or iOS
  // Exclude Chrome/CriOS, Chromium, Edge (WebKit-based)
  return preg_match('/Safari/i', $ua)
    && !preg_match('/Chrome|CriOS|Chromium|Edg/i', $ua);
}

function call_context_function($base_name,$context,...$args)
{
  $base_name = 'tlc\\tts\\' . $base_name;
  $context_name = implode('_',[$base_name,$context]);
  $function_name = function_exists($context_name) ? $context_name : $base_name;

  return $function_name(...$args);
}

function validate_entry_uri()
{
  // Validate the request URI matches our API
  // http[s]://(host[/dir])/[tt|tt.php][?query]
  $request_uri = $_SERVER['REQUEST_URI'];
  // Needs to start with the URI for our app
  if(!str_starts_with($request_uri,APP_URI)) { api_die(); }
  // Good... now strip that off the request (along with the / that follows)
  $request_uri = substr($request_uri,1+strlen(APP_URI));
  // Strip off any query string
  $pos = strpos($request_uri,"?");
  if($pos !== false ) { $request_uri = substr($request_uri,0,$pos); }
  // All we should be left with is tt.php, 405.php, 500.php, tt or nothing
  if(!in_array($request_uri,["", "tt","tt.php","405.php","500.php","admin/","admin.php","preview/"]) ) { api_die(); }
  // We're good!
}
validate_entry_uri();


function gen_token($token_length=25)
{
  $token = '';
  $token_pool = '123456789123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  for($i=0; $i<$token_length; $i++) {
    $index = rand(0,strlen($token_pool)-1);
    $token .= $token_pool[$index];
  }
  return $token;
}

function gen_nonce($key)
{
  $nonce = gen_token(16);
  $_SESSION['nonce'][$key] = $nonce;
  return $nonce;
}

function get_nonce($key)
{
  return $_SESSION['nonce'][$key] ?? null;
}

function validate_nonce($key,$src='POST',$invalidate=true)
{
  $expected = $_SESSION['nonce'][$key] ?? null;
  $actual = (strtolower($src)==='get') ? ($_GET['ttt'] ?? null) : ($_POST['nonce'] ?? null);
  if($actual !== $expected) {
    log_warning("Invalid nonce: ($key:$actual/$expected)",2);
    api_die();
  }
  if($invalidate) { $_SESSION['nonce'][$key] = null; }
}

function validate_ajax_nonce($key)
{
  $expected = $_SESSION['nonce'][$key] ?? null;
  $actual   = $_POST['nonce'];
  if($actual !== $expected) {
    log_warning("Invalid nonce: ($key:$actual/$expected)",2);
    $response = array('success'=>false, 'bad_nonce'=>true );
    echo json_encode($response);
    die();
  }
}

function validate_get_nonce($key,$invalidate=true)   { validate_nonce($key,'GET',$invalidate); }
function validate_and_retain_nonce($key,$src='POST') { validate_nonce($key,$src,false);        }
function validate_and_retain_get_nonce($key)         { validate_nonce($key,'GET',false);       }
