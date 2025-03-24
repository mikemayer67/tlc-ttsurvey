<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

// As the status may need to survive page redirects, it is stored in the SESSION data.

function get_status_message()
{
  $status = $_SESSION['status'] ?? null;
  unset($_SESSION['status']);
  return $status;
}

function set_status_message($msg=null,$level='info')
{
  if($msg) { $_SESSION['status'] = [$level,$msg]; }
  else     { $_SESSION['status'] = null;          }
}

function set_info_status($msg)    { set_status_message($msg,'info');    }
function set_warning_status($msg) { set_status_message($msg,'warning'); }
function set_error_status($msg)   { set_status_message($msg,'error');   }
function clear_status()           { set_status_message('');             }

