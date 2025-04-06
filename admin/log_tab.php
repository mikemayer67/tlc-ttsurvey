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

echo <<<HTMLCTRLS
<div class='log-controls'>
  <label for='log-level-select'>Log Level:</label>
  <select id='log-level-select' name='log-level'>
    <option value=0>Error</option>
    <option value=1>Warning</option>
    <option value=2>Info</option>
    <option value=3>Dev</option>
  </select>
  <label for='log-num-lines'>Max Lines:</label>
  <input id='log-num-lines' type='number' name='num-lines' min=1 max=1000>
  <input id='log-auto-refresh' type='checkbox' name='auto-refresh' value=1>
  <label for 'log-auto-refresh'>Auto-Refresh</label>
  <label for='log-refresh-rate'>Freq</label>
  <input id='log-refresh-rate' name='refresh-rate' type='number' min=1 max=60>
  <span class='units'>minutes</span>
</div>
<div class='log-display-box'>
  <textarea id='log-display' readonly rows=30></textarea>
</div>
<div class='log-button-bar'>
  <a id='log-download-link'>Download</a>
  <a id='log-raw-link'>Plain Text</a>
</div>
HTMLCTRLS;

echo "</form>";

echo "<script src='", js_uri('log','admin'), "'></script>";
