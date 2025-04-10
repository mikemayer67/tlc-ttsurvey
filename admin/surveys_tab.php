<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

$nonce = gen_nonce('admin-surveys');

$form_uri = app_uri('admin');
echo "<form id='admin-surveys' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
add_hidden_submit('action','surveys');


echo "</form>";
echo "<script src='", js_uri('surveys','admin'), "'></script>";
