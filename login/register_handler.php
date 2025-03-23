<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

// Note that all the logic in this file is wrapped up in functions...
//   the very last action in this file is execute the main entry point for 
//   handling post requests from the register form.
//
// As sumch this file should be included using require rather than require_once.

// Except that we will validate the nonce right up front...
validate_post_nonce('register');

function handle_register_form()
{
  log_dev("handle_register_form()");
  print("<h1>REGISTER HANDLER</h1>");
  die();
}

handle_register_form();
