<?php
namespace tlc\tts;

// The tlc-ttsurvey app uses a fairly straightfoward API:
//    http[s]://HOST/PATH_TO_APP/[index.php/]request[?query_args]
//
// Most requests will provide their parameters through POST rather than GET,
//   but a few may need to relay on GET, so we're leaving the option for query_args in the API
//
// The .htaccess file that ships with this app should redirect all calls through index.php (and
//   this this is optional in the API.  If everything is working correctly, all interactions
//   with the app will use this file as the entry point.
//
// To enforce this pattern, we define APP_ENTRY here in index.php and will check that it has
//   been defined at the start of all other .php files.  If one of those other .php files
//   is somehow invoked directly (i.e. the .htaccess has failed to properly redirect), then 
//   die() will be called to immediate terminate the invocation of this app.

define('APP_ENTRY',1);

// Before moving on to anything else, we will use init.php to parse the API call in preparation
//   for executing the actual logic of handling the request.
require_once("./include/init.php");
?>

<h1>You are in the right place (<?=BASE_URI?>)</h1>
<h2><?=APP_DIR?></h2>

<pre> <?=API_COMMAND?></pre>
<pre> <?php print_r(API_QUERY); ?></pre>
<pre> <?php print_r($_SERVER); ?> </pre>
