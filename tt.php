<?php
namespace tlc\tts;

// The tlc-ttsurvey app uses a fairly straightfoward API:
//    http[s]://HOST/PATH_TO_APP/[index.php/]request[?query_args]
//
// Most requests will provide their parameters through POST rather than GET,
//   but a few may need to relay on GET, so we're leaving the option for query_args in the API
//
// The .htaccess file should redirect all calls through index.php.  To enforce this, we define
//   
//   this this is optional in the API.  If everything is working correctly, all interactions
//   with the app will use this file as the entry point.
//
// To enforce this pattern, we define the constant APP_DIR below. If one of the other .php files
//   is somehow invoked directly (i.e. the .htaccess has failed to properly redirect), then 
//   api_die() will be called to immediately terminate the invocation of this app.


define('APP_DIR',dirname(__FILE__));
require_once(APP_DIR.'/include/init.php');
require_once(app_file('include/logger.php'));
require_once(app_file('include/status.php'));

// Developer hacks
todo("remove these hacks");
require_once(app_file('dev/hacks.php'));

try
{
  log_dev("-------------- Start of TT --------------");

  // Common query keys
  $status = $_POST['status'] ?? '';
  if($status) {
    log_dev("status=$status");
    $status = explode('::',$_POST['status']);
    log_dev("status=".print_r($status,true));
    if(count($status) > 1) {
      status_message($status[1],$status[0]);
    } else {
      status_message($status[0]);
    }
  }

  // If there is no active user, present the login page
  require_once(app_file('include/login.php'));

  $active_user = active_userid();
  log_dev(print_r($_COOKIE,true));
  log_dev("active_user = $active_user");

  if(!$active_user) {
    require(app_file('login/setup.php'));
    die();
  }

  // Handle logout and forget token requests
  //   Allow from get or post queries
  if(array_key_exists('logoout',$_REQUEST)) {
    @todo('implement logout');
    handle_logout();
    die();
  }
  if(array_key_exists('forget',$_REQUEST)) {
    forget_user_token($_REQUEST['forget'] ?? '');
    // .. no reason to abort at this point... 
  }

  // If access to the admin tools have been requested, jump to the dashboard
  if(array_key_exists('admin',$_REQUEST)) {
    require(app_file('admin/admin.php'));
    die();
  } 

  // Otherwise, jump to the survey
  require(app_file('survey/survey.php'));
}
catch (\Exception $e)
{
  $file = preg_replace('#'.APP_DIR.'/#', '', $e->getFile());
  internal_error(
    sprintf("Exception %d caught at %s[%s]: %s",
    $e->getCode(),$file,$e->getLine(),$e->getMessage())
  );
}

// Should never get here... but just in case
die();
