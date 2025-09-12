<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/roles.php'));

validate_ajax_nonce('admin-roles');

start_ob_logging();

$roles = $_POST;
unset($roles['ajax']);
unset($roles['nonce']);

$response = array('success'=>true);

foreach( $roles['drop']??null as $drop ) {
  $role = $drop[0];
  $userid = $drop[1];
  if($role === 'primary') {
    Settings::clear('primary_admin');
  } else {
    $rc = drop_user_role($userid,$role);
  }
}

foreach( $roles['add']??null as $add ) {
  $role = $add[0];
  $userid = $add[1];
  if($role === 'primary') {
    Settings::set('primary_admin',$userid);
  } else {
    $rc = add_user_role($userid,$role);
  }
}

$response['nonce'] = gen_nonce('admin-roles');

end_ob_logging();

echo json_encode($response);
die();
