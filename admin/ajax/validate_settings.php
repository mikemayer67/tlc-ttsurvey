<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/settings.php'));
require_once(app_file('include/ajax.php'));

validate_ajax_nonce('admin-settings');

start_ob_logging();

$settings = $_POST;
unset($settings['ajax']);
unset($settings['nonce']);

$errors = Settings::validate($settings);

end_ob_logging();

$response = new AjaxResponse();
if($errors) {
  $response->fail();
  foreach($errors as $k=>$v) { $response->add($k,$v); }
}
$response->send();

die();


