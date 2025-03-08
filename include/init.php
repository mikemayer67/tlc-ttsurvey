<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

function api_die() 
{
  http_response_code(405);
  echo "<h2>API_DIE</h2>";
  require(dirname(__FILE__)."/405.php");
  die; 
}

function internal_error($msg)
{
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
