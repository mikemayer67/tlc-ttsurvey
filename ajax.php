<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

log_dev("-----AJAX HANDLER------");

if($request = $_POST['ajax']) {
  list($scope,$action) = explode('/',$request);
  log_dev("ajax scope='$scope' action='$action'");
  require safe_app_file("$scope/ajax/$action.php");
}

die();


