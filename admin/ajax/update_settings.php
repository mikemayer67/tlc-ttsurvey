<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/ajax.php'));
require_once(app_file('include/imagelib.php'));

validate_ajax_nonce('admin-settings');

start_ob_logging();

$response = new AjaxResponse();

$settings = $_POST;
unset($settings['nonce']);
unset($settings['ajax']);

$errors = Settings::validate($settings);

$errors += ImageLibrary::update_app_logo(
  $_POST['app_logo'] ?? '',
  $_FILES['app_logo_file'] ?? [],
);

if($errors) {
  $response->fail();
  foreach($errors as $k=>$v) { $response->add($k,$v); }
} else {
  Settings::update($settings);
}

$response->add('nonce', gen_nonce('admin-settings'));

end_ob_logging();

$response->send();
die();
