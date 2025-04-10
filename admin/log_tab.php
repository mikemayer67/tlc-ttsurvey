<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

$nonce = gen_nonce('admin-log');

require_once(app_file('admin/elements.php'));
require_once(app_file('include/settings.php'));

$form_uri = app_uri('admin');
echo "<form id='admin-log' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
add_hidden_submit('action','log');

$download_uri = full_app_uri("admin&log=download&ttt=$nonce");
$newtab_uri = full_app_uri("admin&log=newtab&ttt=$nonce");

$log_level_options = [
  [ 0, 'error'   ],
  [ 1, 'warning' ],
  [ 2, 'info'    ],
  [ 3, 'dev'     ],
];
$default_log_level=3;

$num_lines_options = [
  [10,10], [20,20], [50,50], [100,100], [200,200], [500,500], [1000,1000], [0,'&infin;'],
];
$default_num_lines=100;

$refresh_options = [
  [   0,    'off'],
  [   5,  '5 sec'],
  [  10, '10 sec'],
  [  20, '20 sec'],
  [  30, '30 sec'],
  [  45, '45 sec'],
  [  60,  '1 min'],
  [ 120,  '2 min'],
  [ 300,  '5 min'],
  [ 600, '10 min'],
  [1200, '20 min'],
];
$default_refresh = 0;

echo <<<HTMLCTRLS
<div class='log-controls'>
  <div class='log-level'>
    <label>Log Level
      <select id='log-level-select' name='log-level'>
HTMLCTRLS;

foreach($log_level_options as $v) {
  if(is_array($v)) {
    $label = $v[1];
    $v = $v[0];
  } else {
    $label = $v;
  }
  $selected = ($v==$default_log_level ? 'selected' : '');
  echo "<option value=$v $selected>$label</option>"; 
}

echo <<<HTMLCTRLS
      </select>
    </label>
  </div>
  <div class='num-lines'>
    <label>Max Lines
      <select id='num-lines-select' name='num-lines'>
HTMLCTRLS;

foreach($num_lines_options as [$v,$label]) { 
  $selected = ($v==$default_num_lines ? 'selected' : '');
  echo "<option value=$v $selected>$label</option>"; 
}

echo <<<HTMLCTRLS
      </select>
    </label>
  </div>

  <div class='auto-refresh'>
    <label>Refresh Rate:
      <select id='refresh-rate-select' name='refresh-rate'>
HTMLCTRLS;

foreach($refresh_options as [$v,$label]) { 
  $selected = ($v==$default_refresh ? 'selected' : '');
  echo "<option value=$v $selected>$label</option>"; 
}

echo <<<HTMLCTRLS
      </select>
    </label>
  </div>
</div>

<div class='log-display-box'>
  <textarea id='log-display' readonly rows=20></textarea>
</div>
<div class='log-button-bar'>
  <a id='log-download-link' href='$download_uri' class='left' download>Download</a>
  <a id='log-newtab-link' href='$newtab_uri' class='left' target='blank_'>Open in New Tab</a>
  <input id='log-refesh-button' type='submit'  class='right' name='refresh' value='Refresh Now'>
</div>
HTMLCTRLS;

echo "</form>";

echo "<script src='", js_uri('log','admin'), "'></script>";
