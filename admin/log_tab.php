<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

$nonce = gen_nonce('admin-roles');

require_once(app_file('admin/elements.php'));

$form_uri = app_uri('admin');
echo "<form id='admin-log' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
add_hidden_submit('action','log');
echo "</form>";

$js_uri = resource_uri('admin/js/log.js');
echo "<script src='$js_uri'></script>";
