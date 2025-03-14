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

// Let's kick this off by initializing the constants and variables needed by this app
require_once(APP_DIR."/include/init.php");
require_once(app_file('include/const.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('common/page_elements.php'));

try
{
  log_dev("-------------- Start of TT --------------");

  if( isset($_REQUEST['action']) ) {
    todo("Make action only callable via POST");
    $action = strtolower($_REQUEST['action']);

    if($action == "err") {
      internal_error("just testing");
    }

    $junk_cb = function() {
      print("<span>[Menu1]</span>");
      print("<span>[Menu2]</span>");
      print("<span>[Menu3]</span>");
    };

    start_page('login');
    navbar($junk_cb);
    print("<h1>$action</h1>");
    end_page();

  } else {
    require(app_file('user/login.php'));
    require_once(app_file('include/users.php'));
    User::create("someont1y9","Somone Special","Try me is this ok");
    User::from_userid('someone105');
    User::from_userid('someone104');


    print("<pre>" . print_r($_GET,true)     . "</pre>");
    print("<pre>" . print_r($_POST,true)    . "</pre>");
    print("<pre>" . print_r($_REQUEST,true) . "</pre>");
    print("<pre>" . print_r($_SERVER,true)  . "</pre>");
  }

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

