<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/page_elements.php'));

function start_admin_page($tab)
{
  add_admin_navbar($tab);
  echo "<div class='body'>";
}

function end_admin_page()
{
  echo "</div>";
}

function add_admin_navbar($tab)
{
  $form_uri = app_uri('admin');
  $nonce = gen_nonce('admin-navbar');

  echo "<form id='admin-tabs' class='admin-navbar' method='post' action='$form_uri'>";
  add_hidden_input('nonce',$nonce);

  echo "<div class='tabs'>";
  $admin_tabs = glob(app_file('admin/*_tab.php'));
  foreach($admin_tabs as $cur_tab)
  {
    if(preg_match('#/(\w+)_tab.php$#',$cur_tab,$m)) {
      $cur_tab = $m[1];
      $disabled = ($cur_tab === $tab) ? "disabled class='active'" : '';
      echo "<button $disabled name='tab' value='$cur_tab'>$cur_tab</button>";
    }
  }
  echo "</div>";
  echo "</form>";
}

function add_input_section($label,$fields)
{
  echo "<div class='section-header'><div>$label</div></div>";

  $table_class = strtolower(str_replace(' ','-',$label));
  echo "<table class='settings $table_class'>";
  foreach($fields as $field) {
    add_input_field($field);
  }

  echo "</table>";
}

function add_input_field($field)
{
  $key     = $field[0];

  $info    = $field['info'] ?? null;
  $default = $field['default'] ?? '';

  echo "<tr class='$key'>";
  echo "<td class='label'>$key</td>";
  echo "<td class='input'>";
  echo "  <input id='{$key}_input' type='text' name='$key'>";
  echo "</tr>";
  if($info) {
    if(!is_array($info)) { $info = [$info]; }
    echo "<tr class='$key info'><td></td>";
    echo "<td class='$key info'>";
    foreach ($info as $line) {
      echo "<div>$line</div>";
    }
    echo "</tr>";
  }
}
