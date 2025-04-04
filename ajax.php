<?php
namespace tlc\tts;

if(!defined('APP_DIR')) {define('APP_DIR', dirname(__file__));}

require_once(app_file('include/logger.php'));

log_dev("-----AJAX HANDLER------");

if($request = $_POST['ajax']) {
  list($scope,$action) = explode('/',$request);
  require(app_file("$scope/ajax/$action.php"));
}


die();


