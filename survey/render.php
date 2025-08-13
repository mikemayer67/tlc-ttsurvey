<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Render --------------");

echo "<html><head><title>$page_title</title></head><body>";
echo "<h2>Active Userid: $active_user</h2>";

echo "<h1>Title</h1><pre>$survey_title</pre>";
echo "<h1>Content</h1><pre>".print_r($content,true)."</pre>";
echo "</body></html>";

die();
