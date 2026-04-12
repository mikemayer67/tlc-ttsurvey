<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/docs.php'));

$nonce = gen_nonce('admin-docs');


$form_uri = app_uri('admin');
echo "<form id='admin-docs' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
add_hidden_submit('action','docs');

$topic = 'admin_manual';
$doc = new DocsPage($topic);
$doc_title = $doc->title();
$doc_html = $doc->html();

echo <<<HTML
<div class='breadcrumbs'>
  <a href='#' class='breadcrumb' data-topic='$topic'>$doc_title</a>
</div>
<div class='content-box'>
  <div id='docs-display'>$doc_html</div>
</div>
HTML;


echo "</form>";
echo "<script src='", js_uri('docs','admin'), "'></script>";