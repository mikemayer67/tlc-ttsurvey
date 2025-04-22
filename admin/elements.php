<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));

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
  $optional = $field['optional'] ?? false;
  $locked   = key_exists('value',$field);

  $cur_value = $field['value'] ?? Settings::raw($key) ?? '';
  $default   = $field['default'] ?? Settings::default($key) ?? '';

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
  } 
  elseif ($type === 'button' ) {
    // note that actual functionality will be added with javascript
    $label = $field['label'] ?? $key;
    $key = strtolower(str_replace(' ','_',$key));
    echo "<button id='{$key}_button'>$label</button>";
    echo "<span id='{$key}_response' class='button-response'></span>";
  } 
  else {
    echo "<div class=input-box>";
    echo "<input id='{$key}_input' type='$type' name='$key'";
    if(!is_null($min))  { echo " min='$min'";   }
    if(!is_null($max))  { echo " max='$max'";   }
    if(!is_null($step)) { echo " step='$step'"; }
    if(strlen($default)) {
      if($locked) { echo " value='$default'"; }
      else        { echo " placeholder='$default'"; }
    }
    elseif($optional) {
      echo " placeholder='[optional]'";
    } else {
      echo " placeholder='[required]' required";
    }

    if(strlen($cur_value)) { echo " value='$cur_value'"; }
    if($locked)            { echo " disabled"; }

    echo ">";
    echo "<div class='error' name='$key'>error</div></div>";
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

function add_admin_select($key,$users,$current)
{
  echo "<ul>";
  add_admin_info_text($key);
  foreach($current as $userid) {
    $name = $users[$userid];
    echo "<li class='user' userid='$userid' role='$key'>";
    echo "<button class='remove' userid='$userid' from='$key'>-</button>";
    echo "<span class='name'>$name</span>";
    echo "</li>";
  }
  echo "<li class='new user' role='$key'>";
  echo "<button class='add' to='$key'>+</button>";
  echo "<select id='new-$key-select' name='$key'>";
  echo "<option value=''>Add...</option>";
  foreach($users as $userid=>$name) {
    if(!in_array($userid,$current)) {
      echo "<option value='$userid'>$name</option>";
    }
  }
  echo "</select></li></ul>";
}

function add_admin_info_text($key)
{
  $info = admin_info_text($key);
  if($info) {
    echo "<li class='info $key'>$info</li>";
  }
}

function admin_info_text($key)
{
  $rval = "";
  switch($key) {
  case 'primary-admin':
    $rval = <<<INFO
      Can modify the survey app settings, edit user roles, plus anything any survey admin can do.
    INFO;
    break;
  case 'admin':
    $rval = <<<INFO
      Can create, monitor, and change status of surveys, assign editor roles, plus anything an editor can do.
    INFO;
    break;
  case 'content':
    $rval = <<<INFO
      Can modify the structure and content of surveys that are in draft status.
    INFO;
    break;
  case 'tech':
    $rval = <<<INFO
      These are the folks to contact if something is not working correctly.
    INFO;
    break;
  };
  return $rval;
}
