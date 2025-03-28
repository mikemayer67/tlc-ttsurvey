<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

if( $form = $_POST['form']??null ) {
  $handler = app_file("login/{$form}_handler.php");
  if(!file_exists($handler)) {
    internal_error("Unimplemented form handler ($form / $handler)");
  }
  require($handler);
}

require(app_file('login/admin_page.php'));
die();
  
