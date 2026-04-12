<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/ajax.php'));
require_once(app_file('include/docs.php'));

validate_ajax_nonce('admin-docs');

start_ob_logging();

$topic = parse_ajax_string_input('topic');

$doc = new DocsPage($topic);

end_ob_logging();

$rval = [
  'breadcrumb'=>$topic, 
  'title'=>$doc->title(),
  'html'=>$doc->html(),
];
send_ajax_response($rval);

die();