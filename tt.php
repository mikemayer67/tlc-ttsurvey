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
require_once(app_file('include/redirect.php'));
require_once(app_file('include/status.php'));

require_once(app_file('include/db.php'));
verify_required_db_version(3);

session_start();

try
{
  log_dev("-------------- Start of TT --------------");

  // Handle logout and forget token requests
  //   Allow from get or post queries
  if(key_exists('logout',$_REQUEST)) {
    require_once(app_file('include/login.php'));
    logout_active_user();
    header('Location: '.app_uri());
    die();
  }

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

  // If a printable survey was requested, jump to the printable page
  //if(key_exists('printable',$_REQUEST)) {
  //  require(app_file('survey/printable.php'));
  //  die();
  //}

  // If a file download was requested, jump to the requested download page
  if(key_exists('download',$_REQUEST)) {
    $page = $_REQUEST['download'];
    $format = $_REQUEST['f'];
    $path = "$page/download_$format.php";
    require(app_file($path));
    die();
  }

  // If access to the admin tools was requested, jump to the dashboard
  if(key_exists('admin',$_REQUEST)) {
    require(app_file('admin/admin.php'));
    die();
  } 
  
  // If access to the survey summary was requested, jump to the summary page
  if(key_exists('summary',$_REQUEST)) {
    require(app_file('summary/summary.php'));
    die();
  } 
  
  // If there is no active user, present the login page
  require_once(app_file('include/login.php'));
  $active_user = active_userid();

  if(!$active_user) {
    require(app_file('login/core.php'));
    die();
  }

  // Handle any explicit redirect request
  $redirect_page = get_redirect_page();
  if($redirect_page) {
    $page = safe_app_file("login/{$redirect_page}_page.php");
    if(!file_exists($page)) { internal_error("Unimplemented redirect page encountered ($page)"); }
    require($page);
    die();
  }

  // update requests require you be logged in... thus the following two 
  //   appear only after the check for an active user.
  if(key_exists('update',$_REQUEST)) {
    require(app_file('login/'.$_REQUEST['update'].'_page.php'));
    die();
  }
  $form = $_POST['form'] ?? null;
  if( in_array( $form, ['updatepw','updateprof'], true) ) {
    require('login/'.$form.'_handler.php');
    die();
  }

  // If we get a password reset request when there is an active user,
  //   set the status to let the user know that they will need to log
  //   out to handle the reset requst
  if(key_exists('pwreset',$_REQUEST)) {
    set_warning_status(
      "<div style='font-weight:700'>A password recovery request was recieved...</div>".
      "<div>If you wish to change your password, select 'change password' from the user profile maneu.</div>"
    );
  };

  if(key_exists('forget',$_REQUEST)) {
    forget_user_token($_REQUEST['forget'] ?? '');
    // .. no reason to abort at this point... 
  }

  // If response data is being submitted, handle updating the database
  if(key_exists('submit',$_POST) && key_exists('action',$_POST)) {
    require(app_file('survey/handle_submit.php'));
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
    $e->getCode(),$file,$e->getLine(),$e->getMessage(),0)
  );
}

// Should never get here... but just in case
die();
