<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/const.php'));

function get_settings()
{
  $settings_file = app_file(SETTINGS_FILE);
  $json = file_get_contents($settings_file);
  $settings = json_decode($json,true);
  return $settings;
}

function save_settings($settings)
{
  $json = json_encode($settings);
  file_put_contents(app_file(SETTINGS_FILE),$json);
}

function get_setting($key,$default=NULL)
{
  $settings = get_settings();
  return $settings[$key] ?? $default;
}

function update_setting($key,$value)
{
  $settings = get_settings();
  if(empty($value)) { unset($settings[$key]);   } 
  else              { $settings[$key] = $value; }
  save_settings($settings);
}

function clear_setting($key)
{
  update_setting($key,NULL);
}
