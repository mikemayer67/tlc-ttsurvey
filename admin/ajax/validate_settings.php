<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/settings.php'));

validate_and_retain_nonce('admin-settings');

handle_warnings();

$settings = $_POST;
unset($settings['ajax']);
unset($settings['nonce']);

$response = Settings::validate($settings);

// resonse currently contains the list of errors.
//   it shows success if empty
//   it shows failure if not empty
$response['success'] = count($response) == 0;

echo json_encode($response);
die();


