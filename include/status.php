<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

function status_message($msg=null,$level='info')
{
  static $status = null;

  // function is a getter
  if(is_null($msg)) { return $status; }

  // function is a setter
  if($msg) { $status = [$level,$msg]; }
  else     { $status = null;          }

  return $status;
}

function set_info_status($msg)    { status_message($msg,'info');    }
function set_warning_status($msg) { status_message($msg,'warning'); }
function set_error_status($msg)   { status_message($msg,'error');   }
function clear_status()           { status_message('');             }

