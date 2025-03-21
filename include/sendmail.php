<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

function sendmail_profile(...$args)
{
  todo("Implmwent sendmail capability");
  log_dev("sendmail_profile: ".log_array($args));
}
