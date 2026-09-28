<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/settings.php'));
require_once(app_file('include/cookiejar.php'));
require_once(app_file('include/users.php'));

const LOCK_SETTING  = 'admin-lock';
const LOCK_TOKEN    = 'admin-lock-token';
const LOCK_DURATION = 300;

function obtain_admin_lock()
{
  $now = time();
  $own_cur_lock = false;

  $requester = active_userid();
  $requester = User::from_userid($requester);
  $requester = $requester ? $requester->fullname() : 'Site Admin';
  
  $cur_lock = get_setting(LOCK_SETTING);
  if($cur_lock) {
    // There is a current lock out there, let's handle that

    [$cur_lock_token,$lock_expires,$locked_by] = explode('|',$cur_lock);
    $my_token = $_SESSION[LOCK_TOKEN] ?? null;

    if($my_token === $cur_lock_token) {
      // I own the lock... renew it
      $own_cur_lock = true;
    }
    else if ($now < $lock_expires) {
      // someone else owns the lock and it has not yet expired
      $expires_in = $lock_expires - $now;
      log_info("$requester failed to acquire admin lock. Lock held by $locked_by. Expires in $expires_in seconds");
      return array(
        'has_lock'   => false,
        'locked_by'  => $locked_by,
        'expires_in' => $expires_in,
      );
    }
  }

  if(!$own_cur_lock) {
    $_SESSION[LOCK_TOKEN] = gen_token(10);
  }

  $expires = $now + LOCK_DURATION;
  $new_lock = implode('|',array($_SESSION[LOCK_TOKEN],$expires,$requester));

  set_setting(LOCK_SETTING,$new_lock);

  if($own_cur_lock) { log_info("Admin lock renewed by $requester"); }
  else              { log_info("Admin lock aquired by $requester"); }
  return array(
    'has_lock'    => true,
    'expires_in' => LOCK_DURATION,
    'new_token'   => !$own_cur_lock,
  );
}

function check_admin_lock()
{
  $cur_lock = get_setting(LOCK_SETTING);

  if(!$cur_lock) {
    log_dev("Check lock: no lock exists");
    return array(
      'has_lock' => false,
      'locked_by' => null,
    );
  }

  [$cur_lock_token,$lock_expires,$locked_by] = explode('|',$cur_lock);
  $my_token = $_SESSION[LOCK_TOKEN] ?? null;
  $now = time();
  $expires_in = $lock_expires - $now;

  if($my_token !== $cur_lock_token) {
    log_dev("Check lock: lost lock to $locked_by");
    return array(
      'has_lock' => false,
      'locked_by' => $locked_by,
      'expires_in' => $expires_in,
    );
  }

  if($expires_in > 0) {
    log_dev("Check lock: lock will expire in $expires_in seconds");
    return array(
      'has_lock' => true,
      'is_expired' => false,
      'expires_in' => $expires_in,
    );
  } else {
    log_dev("Check lock: lock has expired");
    return array(
      'has_lock' => true,
      'is_expired' => true,
    );
  }
}