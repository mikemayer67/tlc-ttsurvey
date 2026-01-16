<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('admin/admin_lock.php'));
require_once(app_file('include/ajax.php'));

$response = new AjaxResponse();

$lock = obtain_admin_lock();

// not using AjaxResponse here as js is keying off of 'has_lock', not 'success'
send_ajax_response($lock);
die();
