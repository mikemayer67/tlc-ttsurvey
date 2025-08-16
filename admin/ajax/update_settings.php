<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

validate_ajax_nonce('admin-settings');

handle_warnings();

$settings = $_POST;
unset($settings['nonce']);
unset($settings['ajax']);

$errors = Settings::validate($settings);

if($errors) {
  $response = $errors;
  $response['success'] = false;
} else {
  $response = array('success'=>true);

  Settings::update($settings);
}

$response['nonce'] = gen_nonce('admin-settings');

echo json_encode($response);
die();
