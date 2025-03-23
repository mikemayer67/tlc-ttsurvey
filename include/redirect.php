<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

define('REDIRECT_PAGE',    'redirect-page');
define('REDIRECT_DATA',    'redirect-data');
define('REDIRECT_TIMEOUT', 'redirect-timeout');

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
  set_redirect_page(null);
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
  // expire the data if we're after the timeout period
  if( time() > $_SESSION[REDIRECT_TIMEOUT]??0 ) 
  {
    log_dev("Redirect data expired");
    $_SESSION[REDIRECT_DATA] = null;
  }

  // grab the data
  $data = $_SESSION[REDIRECT_DATA] ?? null;

  // clear all session redirect data
  clear_redirect_data();

  // return the data
  return $data;
}

function clear_redirect_data()
{
  $_SESSION[REDIRECT_DATA] = array();
  $_SESSION[REDIRECT_TIMEOUT] = 0;
}
