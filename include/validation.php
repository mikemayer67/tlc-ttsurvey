<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

function adjust_and_validate_user_input($key,&$value,&$error=null)
{
  $value = adjust_user_input($key,$value);
  return validate_user_input($key,$value,$error);
}

function adjust_user_input($key,$value)
{
  $value = trim(stripslashes($value??''));

  switch($key)
  {
  case 'fullname':
    $value = preg_replace('/\'+/',"'",$value);  // condense multiple apostrophes
    $value = preg_replace('/-+/',"-",$value);   // condense multiple hyphens
    $value = preg_replace('/~+/',"~",$value);   // consense multiple tildes
    // fallthrough is intentional
  case 'password':
    $value = preg_replace('/\s+/',' ',$value);  // condense multiple whitespace
    $value = preg_replace('/\s/',' ',$value);   // only use ' ' for whitespace
    break;
  }
  return $value;
}

function validate_user_input($key,$value,&$error=null)
{
  $error = '';

  if($key=='fullname')
  {
    $invalid_end = "'~-";
    $valid = "A-Za-z\x{00C0}-\x{00FF} .'~-";
    if(preg_match("/([^$valid])/",$value,$m)) {
      $error = "names cannot contain $m[1]";
    }
    elseif(preg_match("/\.\S/",$value,$m)) {
      $error = "name cannot contain period";
    }
    elseif(preg_match("/(?:^|\s)([$invalid_end])/",$value,$m)) {
      $error = "names cannot start with $m[1]";
    }
    elseif(preg_match("/([$invalid_end])(?:$|\s)/",$value,$m)) {
      $error = "names cannot end with $m[1]";
    }
    elseif(strlen($value)<2) {
      $error = "no initials, please";
    }
    elseif(!str_contains($value,' ')) {
      $error = "full name, please";
    }
  }
  elseif($key=='userid')
  {
    if(strlen($value)<8)      { $error = "too short"; }
    elseif(strlen($value)>16) { $error = "too long"; }
    elseif(preg_match("/\s/",$value)) {
      $error = "cannot contain spaces";
    }
    elseif(preg_match("/^[^a-zA-Z]/",$value)) {
      $error = "must start with a letter";
    }
    elseif(!preg_match("/^[a-zA-Z][a-zA-Z0-9]+$/",$value)) {
      $error = "letters/numbers only";
    }
  }
  elseif($key=='password')
  {
    if(strlen($value)<8)       { $error = "too short"; }
    elseif(strlen($value)>128) { $error = "too long"; }
    elseif(!preg_match("/[a-zA-Z]/",$value)) {
      $error = "must contain at least one letter";
    }
    elseif(preg_match("/([^a-zA-Z0-9 !@%^*_=~,.-])/",$value,$m)) 
    {
      $error = "cannot contain ($m[1])";
    }
  }
  elseif($key=="email")
  {
    # email is optional, so empty is ok
    if($value)
    {
      $email = filter_var($value,FILTER_VALIDATE_EMAIL);
      if(!$email) { $error = "invalid format"; }
    }
  }

  return strlen($error) == 0;
}

