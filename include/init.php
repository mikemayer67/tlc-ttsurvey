<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

define('APP_URI',preg_replace("/\/[^\/]+$/","",$_SERVER['SCRIPT_NAME']));

function api_die() 
{
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
  log_error("[$errid]: $msg");
  http_response_code(500);
  require(app_file('500.php'));
  die;
}

function app_file($path)              { return APP_DIR . "/$path";        }
function app_uri($uri='tt.php?ttt=1') { return APP_URI . "/$uri";         }
function img_uri($filename)           { return APP_URI . "/img/$filename"; }
function css_uri($filename)           { return APP_URI . "/css/$filename"; }
function js_uri($filename)            { return APP_URI . "/js/$filename";  }

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

