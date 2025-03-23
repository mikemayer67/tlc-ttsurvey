<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("GET=".print_r($_GET,true));
log_dev("POST=".print_r($_POST,true));
log_dev("SESSION=".print_r($_SESSION,true));

// Handle request to forget an access token
if(key_exists('forget',$_GET)) {
  validate_get_nonce('login');
  $forget     = $_GET['forget'] ?? null;
  forget_user_token($forget);
  header('Location: '.app_uri());
  die();
}

// Handle POST requests
if( $form=$_POST['form']??null ) {
  $handler = "login/{$form}_handler.php";
  log_dev("hander=$handler");
  $handler = app_file($handler);
  log_dev("hander=$handler");
  if(!file_exists($handler)) {
    internal_error("Unimplemented form handler ($form / $handler)");
  }
  require($handler);
  die();
}

// Handle GET requests for other login pages
$page = $_GET['p'] ?? 'login';
$page = app_file("login/{$page}_page.php");
if(!file_exists($page)) { 
  api_die(); 
}
require($page);
die();


