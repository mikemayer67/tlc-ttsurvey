<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

// Look for login nonce...
//   If it exists, see if there is additional login info
//
//     action=login => attemt to login with userid/password
//     action=recover => attmpt to login with userid/token
//
//
