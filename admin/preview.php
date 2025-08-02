<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Admin Dashboard --------------");
log_dev("REQUEST: ".print_r($_REQUEST,true));
log_dev("SESSION: ".print_r($_SESSION,true));


require_once(app_file('include/login.php'));
$active_user = active_userid();
log_dev("active: $active_user");
