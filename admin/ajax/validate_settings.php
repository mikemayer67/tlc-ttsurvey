<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

handle_warnings();

log_dev("validate_settings: ".print_r($_POST,true));

$response = array("success"=>true);

if($timezone = $_POST['timezone'] ?? null) {
  $timezone = trim($timezone);
  if(!date_default_timezone_set($timezone)) {
    $response['success'] = false;
    $response['timezone'] = "unrecognized timezone";
  }
}

if($app_logo = $_POST['app_logo'] ?? null) {
  $app_logo = trim($app_logo);
  $imgfile = app_file("img/$app_logo");
  if(!(file_exists($imgfile) && getimagesize($imgfile))) {
    $response['success'] = false;
    $response['app_logo'] = "not found on server";
  }
}

if($admin_email = $_POST['admin_email'] ?? null) {
  $admin_email = trim($admin_email);
  if(!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
    $response['success'] = false;
    $response['admin_email'] = "invalid email address";
  }
}

$to = $_POST['pwreset_timeout'] ?? null;
if(isset($to)) {
  $to = trim($to);
  if(strlen($to) > 0) {
    if(!( is_numeric($to) && $to>0 ) ) {
      $response['success'] = false;
      $response['pwreset_timeout'] = "not a positive number";
    }  
  }
}

$len = $_POST['pwreset_length'] ?? null;
if(isset($len)) {
  $len = trim($len);
  if(strlen($len) > 0) {
    if(!( is_numeric($len) && is_integer(1*$len) && $len>=4 && $len <=20 ) ) {
      $response['success'] = false;
      $response['pwreset_length'] = "not an integer in the range 4-20";
    }  
  }
}

$host = $_POST['smtp_host'] ?? null;
if(isset($host)) {
  $host = trim($host);
  if(strlen($host)==0) {
    $response['success'] = false;
    $response['smtp_host'] = "missing";
  }
  elseif(!filter_var($host,FILTER_VALIDATE_DOMAIN)) {
    $response['success'] = false;
    $response['smtp_host'] = "invalid domain name";
  }
}

$username = $_POST['smtp_username'] ?? null;
if(isset($username)) {
  $username = trim($username);
  if(strlen($username)==0) {
    $response['success'] = false;
    $response['smtp_username'] = "missing";
  }
}

$password = $_POST['smtp_password'] ?? null;
if(isset($password)) {
  $password = trim($password);
  if(strlen($password)==0) {
    $response['success'] = false;
    $response['smtp_password'] = "missing";
  }
}

$port = $_POST['smtp_port'] ?? null;
if(isset($port)) {
  $port = trim($port);
  if(strlen($port) > 0) {
    if(!( is_numeric($port) && is_integer(1*$port) && $port>0)) {
      $response['success'] = false;
      $response['smtp_port'] = "not a positive integer";
    }  
  }
}

if($email = $_POST['smtp_reply_email'] ?? null) {
  $email = trim($email);
  if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['success'] = false;
    $response['smtp_reply_email'] = "invalid email address";
  }
}

log_dev("Result: ".print_r($response,true));

echo json_encode($response);
die();


