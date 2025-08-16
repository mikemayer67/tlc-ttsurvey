<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

define('REDIRECT_PAGE',    'redirect-page');
define('REDIRECT_DATA',    'redirect-data');
define('REDIRECT_TIMEOUT', 'redirect-timeout');

// The functions in this file allow for data to be retained across a http
//   redirection back to the app's main entry point.  
//
// As there is no mechaism for sending POST data with the redirection, this
//   data is stored in the SESSION data.  There are two pieces to this data:
//     - page: (string) contains the page/action to be resumed after the redirect
//     - data: (array) auxilary data needed to resume the page/action after the redirect 
//   The actual usage of this data is determined as needed.
//
// Because this is meant to be a sort of "jump cable" across the redirect, this
//   data should not persist to subsequent app entries.  Once the data has been
//   retrieved with get_redirect_page or get_redirect_data, it is immediately
//   purged from the SESSION data.

function set_redirect_page($page)
{
  $_SESSION[REDIRECT_PAGE] = $page;
}

function get_redirect_page()
{
  $page = $_SESSION[REDIRECT_PAGE] ?? null;
  clear_redirect_page();
  return $page;
}

function clear_redirect_page()
{
  unset($_SESSION[REDIRECT_PAGE]);
}


function add_redirect_data($key,$value)
{
  if(!key_exists(REDIRECT_DATA,$_SESSION)) {
    $_SESSION[REDIRECT_DATA] = array();
  }
  $_SESSION[REDIRECT_DATA][$key] = $value;
  $_SESSION[REDIRECT_TIMEOUT] = time() + 10;
}

function get_redirect_data()
{
  if(!key_exists(REDIRECT_TIMEOUT,$_SESSION)) {
    unset($_SESSION[REDIRECT_DATA]);
    return null;
  }
  if(!key_exists(REDIRECT_DATA,$_SESSION)) {
    unset($_SESSION[REDIRECT_TIMEOUT]);
    return null;
  }

  // expire the data if we're after the timeout period
  if( time() > $_SESSION[REDIRECT_TIMEOUT] ) 
  {
    clear_redirect_data();
    return null;
  }

  // grab the data
  $data = $_SESSION[REDIRECT_DATA];

  // clear all session redirect data
  clear_redirect_data();

  // return the data
  return $data;
}

function clear_redirect_data()
{
  unset($_SESSION[REDIRECT_DATA]);
  unset($_SESSION[REDIRECT_TIMEOUT]);
}
