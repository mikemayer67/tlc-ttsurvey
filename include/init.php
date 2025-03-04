<?php
namespace tlc\tts;

function api_die() 
{
  http_response_code(405);
  require("./404.php");
  die; 
}

if(!defined('APP_ENTRY')) { api_die(); }

# Find any "REDIRECT_XXX" server variables that don't have a corresponding XXX variable.
# Make a copy of these without the REDIRECT prefix.  (There is an implicit assumption that
# multiple redirects won't change the effective value...)
foreach ($_SERVER as $k => $v) {
  if(preg_match('/^(REDIRECT_)+(.*)$/',$k, $m)) {
    $nk = $m[2];
    if( ! array_key_exists($nk,$_ENV) ) {
      $_SERVER[$nk] = $v;
    }
  }
}

# Extract the BASE_URI from the SERVER variables
define("BASE_URI", rtrim($_SERVER['BASE_URI'],'/'));

# Construct the web app's base directory from the web host's document root and the 
#   base URI for the app
define("APP_DIR",  rtrim($_SERVER['DOCUMENT_ROOT'],'/') . BASE_URI);

# Parse the URI to ferret out the API components
function api_parse_uri()
{
  $request = $_SERVER['REQUEST_URI'];
  $base_re = "/*".BASE_URI;
  $index_re = "/*index.php";
  $command_re = "([^?]+)";
  $query_re = "(?:[?](.*))";
  $regex = "#(?:$base_re)?(?:$index_re)?/*$command_re?$query_re?#";

  if(!preg_match($regex,$request,$m)) {
    api_die();
  }

  if(empty($m[1])) {
    define('API_COMMAND','');
    if(!empty($m[2])) { api_die(); }
    define('API_QUERY',array());
  } else {
    define('API_COMMAND',$m[1]??"");
    $api_command = array();
    if(!empty($m[2])) {
      $query = explode("&",$m[2]);
      foreach( $query as $q ) {
        $kv = explode("=",$q);
        switch(count($kv)) {
        case 1:
          $api_command[$kv[0]] = "";
          break;
        case 2:
          $api_command[$kv[0]] = $kv[1];
          break;
        default:
          api_die();
          break;
        }
      }
    }
    define('API_QUERY',$api_command);
  }
}
api_parse_uri();

