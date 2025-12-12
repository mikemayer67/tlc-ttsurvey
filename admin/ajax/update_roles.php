<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/roles.php'));
require_once(app_file('include/settings.php'));

validate_ajax_nonce('admin-roles');

start_ob_logging();

log_dev(print_r($_POST,true));

$response = array('success'=>true);

foreach( $_POST['drop']??[] as $drop ) {
  $role = $drop[0];
  $userid = strtolower($drop[1]);
  if($role === 'primary') {
    Settings::clear('primary_admin');
  } else {
    $rc = drop_user_role($userid,$role);
  }
}

foreach( $_POST['add']??[] as $add ) {
  $role = $add[0];
  $userid = strtolower($add[1]);
  if($role === 'primary') {
    Settings::set('primary_admin',$userid);
  } else {
    $rc = add_user_role($userid,$role);
  }
}

set_setting('summary_flags',$_POST['summary_flags']??0);

$response['nonce'] = gen_nonce('admin-roles');

end_ob_logging();

echo json_encode($response);
die();
