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

function app_file($path)
{
  return APP_DIR . "/$path";
}

function validate_entry_uri()
{
  $script_name = basename($_SERVER['SCRIPT_NAME']);
  $request_uri = $_SERVER['REQUEST_URI'];
  print("<pre>".APP_DIR."</pre>");
  print("<pre>".APP_URI."</pre>");
  print("<pre>1: $request_uri</pre>");

  if(!str_starts_with($request_uri,APP_URI)) { api_die(); }
  $request_uri = substr($request_uri,1+strlen(APP_URI));
  print("<pre>2:$request_uri</pre>");

  $pos = strpos($request_uri,"?");
  if($pos !== false ) {
    $request_uri = substr($request_uri,0,$pos);
  }
  if(!preg_match("/^(tt|tt.php)?$/",$request_uri)) { api_die(); }
  print("<pre>3:$request_uri</pre>");

}
validate_entry_uri();

