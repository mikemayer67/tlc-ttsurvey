<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

$nonce = gen_nonce('admin-maintenance');

$form_uri = app_uri('admin');
echo "<form id='admin-maintenance' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
add_hidden_submit('action','maintenance');

echo "<div class='section-header'>Unused Database Entries</div>";
echo "<ul class='action-list'>";
echo "<li><a href='#' class='maintenance options'>Remove all unused select options</a></li>";
echo "</ul>";
echo "</div>";
echo "</form>";

echo "<script src='", js_uri('maintenance','admin'), "'></script>";
