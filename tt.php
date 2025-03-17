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

try
{
  log_dev("-------------- Start of TT --------------");

  // If admin pages have been requested, jump to those now...
  if(array_key_exists('admin',$_REQUEST)) {
    require(app_file('admin/admin.php'));
    die();
  } 

  // Developer hacks
  todo("remove these hacks");
  if(array_key_exists('dev',$_GET)) {
    require(app_file('dev.php'));
    die();
  } 
  if(array_key_exists('demo',$_GET)) {
    require(app_file('demo.php'));
    die();
  } 

  // Active Survey
  //   If there is no active survey, were not going to ask 
  //   anyone to sign in...
  require_once(app_file('include/surveys.php'));

  $active_survey_title = active_survey_title();
  if(!$active_survey_title) {
    require(app_file('pages/no_survey.php'));
    die();
  }

  // User login status
  require_once(app_file('include/login.php'));

  $active_user = active_userid();
  log_dev(print_r($_COOKIE,true));
  log_dev("active_user = $active_user");

  if(!$active_user) {
    require(app_file('pages/login.php'));
    die();
  }

  print("<h1>$active_survey_title</h1>");
  print("<pre>".print_r($_GET,true)."</pre>");
  print("<pre>".print_r($_POST,true)."</pre>");

//  if( isset($_REQUEST['action']) ) {
//    todo("Make action only callable via POST");
//    $action = strtolower($_REQUEST['action']);
//
//    if($action == "dev") {
//      require(app_file('dev.php'));
//    }
//    elseif($action == "demo") {
//      $junk_cb = function() {
//        print("<span>[Menu1]</span>");
//        print("<span>[Menu2]</span>");
//        print("<span>[Menu3]</span>");
//      };
//
//      start_page('junk');
//      navbar($junk_cb);
//      print("<h1>$action</h1>");
//      print("<pre>".print_r($_SERVER,true)."</pre>");
//      end_page();
//    }
//    else {
//      api_die();
//    }
//
//  } else {
//    require(app_file('pages/login.php'));
//  }
//
}
catch (\Exception $e)
{
  $file = $e->getFile();
  if(str_starts_with($file,APP_DIR)) { $file = substr($file,1+strlen(APP_DIR)); }

  internal_error(
    sprintf("Exception %d caught at %s[%s]: %s",
      $e->getCode(),$file,$e->getLine(),$e->getMessage())
  );
}

