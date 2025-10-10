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

define('APP_DIR',realpath(dirname(__FILE__)));
require_once(APP_DIR.'/include/init.php');
require_once(app_file('include/logger.php'));
require_once(app_file('include/status.php'));

session_start();

// Developer hacks
todo("remove these hacks");
require_once(app_file('dev/hacks.php'));

try
{
  log_dev("-------------- Start of TT --------------");

  // If ajax request, jump to ajax handling
  if(key_exists('ajax',$_POST)) {
    list($scope,$action) = explode('/',$_POST['ajax']);
    if(!isset($action)) { http_response_code(405); die(); }
    require safe_app_file("$scope/ajax/$action.php");
    die();
  }

  // If a survey preview was requested, jump to the preview page
  if(key_exists('preview',$_REQUEST)) {
    require(app_file('survey/preview.php'));
    die();
  }

  // If access to the admin tools have been requested, jump to the dashboard
  if(key_exists('admin',$_REQUEST)) {
    require(app_file('admin/admin.php'));
    die();
  } 

  // If there is no active user, present the login page
  require_once(app_file('include/login.php'));
  $active_user = active_userid();

  if(!$active_user) {
    require(app_file('login/core.php'));
    die();
  }

  // Handle logout and forget token requests
  //   Allow from get or post queries
  if(key_exists('logout',$_REQUEST)) {
    logout_active_user();
    header('Location: '.app_uri());
    die();
  }

  // password change requests require you be logged in... thus this appears
  //   only after the check for an active user.
  if(key_exists('changepw',$_REQUEST)) {
    require(app_file('login/changepw_page.php'));
    die();
  }

  // If we get a password reset request when there is an active user,
  //   set the status to let the user know that they will need to log
  //   out to handle the reset requst
  if(key_exists('pwreset',$_REQUEST)) {
    log_dev("set_warning_status");
    set_warning_status(
      "<div style='font-weight:700'>A password recovery request was recieved...</div>".
      "<div>If you wish to change your password, select 'change password' from the user profile maneu.</div>"
    );
  };

  if(key_exists('forget',$_REQUEST)) {
    forget_user_token($_REQUEST['forget'] ?? '');
    // .. no reason to abort at this point... 
  }

  // Otherwise, jump to the survey
  require(app_file('survey/survey.php'));
}
catch (\Exception $e)
{
  $file = preg_replace('#'.APP_DIR.'/#', '', $e->getFile());
  internal_error(
    sprintf("Exception %d caught at %s[%s]: %s",
    $e->getCode(),$file,$e->getLine(),$e->getMessage(),0)
  );
}

// Should never get here... but just in case
die();
