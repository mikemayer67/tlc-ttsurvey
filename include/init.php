<?php
namespace tlc\tts;

# Find any "REDIRECT_XXX" server variables that don't have a corresponding XXX variable.
# Make a copy of these without the REDIRECT prefix.  (There is an implicit assumption that
# multiple redirects won't change the effective value...)
foreach ($_SERVER as $k => $v) {
  if(preg_match('/^(REDIRECT_)+(.*)$/',$k, $m)) {
    $nk = $m[2];
    if( ! array_key_exists($nk,$_ENV) ) {
      $_SERVER[$nk] = $v;
    }
  }
}

# Extract the BASE_URI from the SERVER variables
define("BASE_URI", rtrim($_SERVER['BASE_URI'],'/'));

# Construct the web app's base directory from the web host's document root and the 
#   base URI for the app
define("APP_DIR",  rtrim($_SERVER['DOCUMENT_ROOT'],'/') . BASE_URI);

