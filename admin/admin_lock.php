<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));
require_once(app_file('include/settings.php'));
require_once(app_file('include/login.php'));
require_once(app_file('include/users.php'));

const LOCK_SETTING  = 'admin-lock';
const LOCK_TOKEN    = 'admin-lock-token';
const LOCK_DURATION = 300;

function obtain_admin_lock()
{
  log_dev('otain_admin_lock: '.$_SERVER['HTTP_USER_AGENT']??'unknown');
  $now = time();
  $own_cur_lock = false;

  $cur_lock = get_setting(LOCK_SETTING);
  if($cur_lock) {
    // There is a current lock out there, let's handle that

    [$cur_lock_token,$lock_expires,$locked_by] = explode('|',$cur_lock);
    $my_token = $_SESSION[LOCK_TOKEN] ?? null;

    if($my_token === $cur_lock_token) {
      // I own the lock... renew it
      log_dev("user owns lock, renew it");
      $own_cur_lock = true;
    }
    else if ($now < $lock_expires) {
      // someone else owns the lock and it has not yet expired
      log_dev("failed to obtain lock");
      return array(
        'has_lock'   => false,
        'locked_by'  => $locked_by,
        'expires_in' => $lock_expires - $now,
      );
    }
  }

  $requester = active_userid();
  $requester = User::from_userid($requester);
  $requester = $requester ? $requester->fullname() : 'Site Admin';
  
  if(!$own_cur_lock) {
    $_SESSION[LOCK_TOKEN] = gen_token(10);
    log_dev("new token: ".$_SESSION[LOCK_TOKEN]);
  }

  $expires = $now + LOCK_DURATION;
  $new_lock = implode('|',array($_SESSION[LOCK_TOKEN],$expires,$requester));

  set_setting(LOCK_SETTING,$new_lock);

  return array(
    'has_lock'    => true,
    'expiress_in' => LOCK_DURATION,
    'new_token'   => !$own_cur_lock,
  );
}
