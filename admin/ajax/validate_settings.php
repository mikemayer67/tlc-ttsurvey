<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

handle_warnings();

log_dev("validate_settings: ".print_r($_POST,true));

$response = array("success"=>true);

if($timezone = $_POST['timezone'] ?? null) {
  if(!date_default_timezone_set($timezone)) {
    $response['success'] = false;
    $response['timezone'] = "Unrecognized timezone: $timezone";
  }
}

if($app_logo = $_POST['app_logo'] ?? null) {
  if(!file_exists(app_file("img/$app_logo"))) {
    $response['success'] = false;
    $response['app_logo'] = "Cannot find $app_logo on server";
  }
}

echo json_encode($response);
die();


