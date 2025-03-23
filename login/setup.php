<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

// Handle request to forget an access token
//
// This is a special case that doesn't fit well into the GET/POST
//   requests handled below... so we'll just handle it now

if(key_exists('forget',$_GET)) {
  validate_get_nonce('login');
  $forget     = $_GET['forget'] ?? null;
  forget_user_token($forget);
  header('Location: '.app_uri());
  die();
}

// Handle POST requests
//
// Need to handle these before GET requests so that we can fall
//   through to loading the main login form if the handler decides
//   that we need to return to the main login page.

if( $form=$_POST['form']??null ) {
  $handler = "login/{$form}_handler.php";
  $handler = app_file($handler);
  if(!file_exists($handler)) {
    internal_error("Unimplemented form handler ($form / $handler)");
  }
  require($handler);
}

// Handle GET requests specified by the p query key
//
// If none is specified, we want to load the main login page.
//   This includes cases where the POST handler decided that
//   this we should return to the main login page.

$page = $_GET['p'] ?? 'login';
$page = app_file("login/{$page}_page.php");
if(!file_exists($page)) { 
  api_die(); 
}
require($page);
die();


