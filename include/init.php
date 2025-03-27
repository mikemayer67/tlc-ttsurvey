<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

define('APP_URI',preg_replace("/\/[^\/]+$/","",$_SERVER['SCRIPT_NAME']));

class BadInput extends \Exception {}

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
  if(defined('RENDERING_500_PHP')) { return; }

  require_once('include/logger.php');
  $errid = bin2hex(random_bytes(3));
  log_error("[$errid]: $msg",2);
  http_response_code(500);
  require(app_file('500.php'));
  die;
}

// in order to prevent css files from caching, we append a changing query
// string to the URL for the css file... but this should only be needed
// in a development environment.
function no_cache() { return is_dev() ? ("?v=" . rand()) : ''; }

function app_file($path)   { return APP_DIR . "/$path"; }

function app_uri($q=null)  { return APP_URI . "/tt.php" . ($q ? "?$q" : '');  }
function img_uri($img)     { return APP_URI . "/img/$img"     . no_cache();   }
function css_uri($css)     { return APP_URI . "/css/$css.css" . no_cache();   }
function js_uri($filename) { return APP_URI . "/js/$filename" . no_cache();   }

function full_app_uri($q=null) {
  $scheme = parse_url($_SERVER['HTTP_REFERER'],PHP_URL_SCHEME);
  $host = parse_url($_SERVER['HTTP_REFERER'],PHP_URL_HOST);
  $path = parse_url($_SERVER['HTTP_REFERER'],PHP_URL_PATH);
  log_dev("full_app_uri($q) : '$scheme' '$host' '$path'");
  return "$scheme://$host$path" . ($q ? "?$q" : '');
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
  if(!in_array($request_uri,["", "tt","tt.php","405.php","500.php","admin/","admin.php"]) ) { api_die(); }
  // We're good!
}
validate_entry_uri();


function gen_token($token_length=25)
{
  $access_token = '';
  $token_pool = '123456789123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  for($i=0; $i<$token_length; $i++) {
    $index = rand(0,strlen($token_pool)-1);
    $access_token .= $token_pool[$index];
  }
  return $access_token;
}

function gen_nonce($key)
{
  $nonce = gen_token(16);
  $_SESSION['nonce'][$key] = $nonce;
  return $nonce;
}

function get_nonce($key)
{
  $nonce = $_SESSION['nonce'][$key] ?? null;
  $_SESSION['nonce'][$key] = null;
  return $nonce;
}

function validate_nonce($key,$nonce,$msg="Invalid Nonce",$trace=2)
{
  $expected = get_nonce($key);
  if($nonce !== $expected) {
    log_warning("$msg ($key:$nonce/$expected)",$trace);
    api_die();
  }
}

function validate_post_nonce($key,$msg="Invalid Nonce") { validate_nonce($key,$_POST['nonce'],$msg,3); }
function validate_get_nonce($key,$msg="Invalid Nonce")  { validate_nonce($key,$_GET['ttt'],$msg,3); }

