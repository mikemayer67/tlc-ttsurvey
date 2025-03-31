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
  $key      = $field[0];
  $type     = $field['type'] ?? 'text';
  $min      = $field['min'] ?? null;
  $max      = $field['max'] ?? null;
  $step     = $field['step'] ?? null;
  $options  = $field['options'] ?? null;
  $info     = $field['info'] ?? null;
  $default  = $field['default'] ?? '';
  $optional = $field['optional'] ?? false;
  $editable = $field['editable'] ?? true;

  $cur_value = get_setting($key,'');

  echo "<tr class='$key'>";
  echo "<td class='label'>$key</td>";
  echo "<td class='input'>";
  if($options) {
    if(strlen($default) && strlen($cur_value)==0) { $cur_value = $default; }
    echo "<select id='{$key}_select' name='$key'>";
    foreach ($options as $index=>$option) {
      echo "<option value='$index'";
      if(strlen($cur_value) && $cur_value == $index) { 
        echo " selected='selected'";
      } 
      echo ">$option</option>";
    }
    echo "</select>";
  } else {
    echo "<input id='{$key}_input' type='$type' name='$key'";
    if(!is_null($min))  { echo " min='$min'";   }
    if(!is_null($max))  { echo " max='$max'";   }
    if(!is_null($step)) { echo " step='$step'"; }
    if(strlen($default)) {
      if($editable) { echo " placeholder='$default'"; }
      else          { echo " value='$default'"; }
    }
    elseif($optional) {
      echo " placeholder='[optional]'";
    } else {
      echo " placeholder='[required]' required";
    }
    if(strlen($cur_value)) {
      echo " value='$cur_value'";
    }
    if(!$editable) {
      echo " disabled";
    }
    echo ">";
  }
  echo "</td></tr>";
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
