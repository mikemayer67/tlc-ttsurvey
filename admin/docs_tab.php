<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

$nonce = gen_nonce('admin-docs');


$form_uri = app_uri('admin');
echo "<form id='admin-docs' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
add_hidden_submit('action','docs');

$admin_manual = '@@@ Fill this in';

echo <<<HTML
<div class='breadcrumbs'>
  <a href='#' class='breadcrumb' data-page='admin_manual'>Admin Manual</a>
  <a href='#' class='breadcrumb' data-page='admin_roles'>Admin Roles</a>
  <a href='#' class='breadcrumb' data-page='admin_roles'>Admin Roles</a>
</div>
<div class='content-box'>
  <textarea id='docs-display' readonly rows=25>$admin_manual</textarea>
</div>
HTML;


echo "</form>";
echo "<script src='", js_uri('docs','admin'), "'></script>";