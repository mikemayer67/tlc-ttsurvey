<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

function sendmail_profile(...$args)
{
  todo("Implement sendmail capability");
  log_dev("sendmail_profile: ".log_array($args));
}

function sendmail_recovery(...$args)
{
  todo("Implement sendmail_recovery capaiblity");
  log_dev("sendmail_recovery: ".log_array($args));
}
