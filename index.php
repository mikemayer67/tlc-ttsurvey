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
log_warning("I am a teapot");
set_log_level(LOGGER_WARN);
log_warning("I am a spoon");
set_log_level(LOGGER_DEV);

?>

<h1>You are in the right place (<?=APP_DIR?>)</h1>
<h2><?=APP_DIR?></h2>
<pre> <?php print_r($_SERVER); ?> </pre>
