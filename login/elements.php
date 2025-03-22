<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

/****************************************************************
 **
 ** Common Look and Feel for all login forms
 **
 ****************************************************************/

function start_login_form($header,$name) 
{
  $form_uri = app_uri();
  $nonce = gen_token(16);
  $_SESSION['nonce'][$name] = $nonce;

  echo "<div id='ttt-login' class='$name ttt-card'>";
  echo "<header>$header</header>";
  echo "<form class='login' method='post' action='$form_uri'>";
  add_hidden_input('nonce',$nonce);
  add_hidden_input('refresh',1);
  add_hidden_input('status','');
  return $nonce;
}

function add_hidden_input($name,$value)
{
  echo "<input type='hidden' name='$name' value='$value'>";
}

function close_login_form()
{
  // must close all DOM elements opened in ttt-login
  echo "</form></div>";
}

function add_resume_buttons($nonce)
{
  $tokens = cached_tokens();
  if(!$tokens) { return; }
  
  $icon = img_uri('icons8-delete_sign.png');
  $class = 'resume token';
  $uri = app_uri("ttt=$nonce");

  echo "<input type='submit' style='display:none'>";
  echo "<div class='resume-label'>Resume Survey as:</div>";
  echo "<div class='resume-box'>";
  foreach($tokens as $userid=>$token) {
    $user = User::from_userid($userid);
    if($user) {
      $fullname = $user->fullname();
      $value = "resume:$userid:$token";
      echo "<div class='button-box'>";
      echo "<button class='$class' name='resume' value='$userid:$token' formnovalidate>";
      echo "<div class='fullname'>$fullname</div>";
      echo "<div class='userid'>$userid</div>";
      echo "</button>";
      echo "<div class='forget'>";
      echo "<a href='$uri&forget=$userid' data-userid='$userid'><img src='$icon'></a>";
      echo "</div>"; // forget
      echo "</div>"; // button-box
    }
  }
  echo "</div>";
  echo "<div class='resume-label'>Or Login as:</div>";
}

function add_login_input($type,$kwargs=array())
{
  $name = $kwargs['name'] ?? $type;
  $label = $kwargs['label'] ?? ucwords($name);
  $value = stripslashes($kwargs['value'] ?? '');
  $optional = $kwargs['optional'] ?? False;
  $info = $kwargs['info'] ?? null;
  $id = "ttt-input-$name";

  echo "<!-- $label -->";
  echo "<div class='input $name'>";

  # add label box

  echo "<div class='label-box'>";
  echo "<label for='$id'>$label</label>";
  if($info) { 
    $info_link = "ttt-$name-info";
    $icon_url = img_uri('icons8-info.png');
    $info_icon = "<img src='$icon_url'>";
    $info_trigger = "<a class='info-trigger' data-target='$info_link'>$info_icon</a>";
    echo($info_trigger); 
  }
  echo "<div class='error $name'></div>";
  echo "</div>"; // label-box

  # add input fields
  
  $value = $value ? "value=\"$value\"" : "";

  $empty = $optional ? '' : 'empty';
  $required = $optional ? "placeholder='[optional]'" : 'required';
  $ac = "autocomplete='$type'";
  
  if($type=='new-password') 
  {
    echo "<input id='$id' type='password' class='text-entry entry empty primary' name='$name' required $ac>";
    echo "<input type='password' class='text-entry entry empty confirm' name='$name-confirm' required $ac>";
  }
  else
  {
    switch($type) {
    case 'password': $value = '';    break;
    case 'email':                    break;
    default:         $type = 'text'; break;
    }
    echo "<input id='$id' type='$type' class='text-entry $empty' name='$name' $value $required $ac>";
  }

  # add info box

  if($info)
  {
    echo "<div id='$info_link' class='info-box'>";
    echo "<div class='info'><p>$info</p></div>";
    echo "</div>";
  }

  # close the input box
  echo "</div>";  // input
}

function add_login_checkbox($name, $kwargs=array())
{
  $label = $kwargs['label'] ?? ucwords($name);
  $checked = stripslashes($kwargs['value'] ?? False) ? 'checked' : '';
  $info = $kwargs['info'] ?? null;
  $id = "ttt-cb-$name";

  echo "<!-- $label -->";
  echo "<div class='input $name'>";

  if($info) {
    $info_cb = "ttt-$name-info-cb";
    echo "<input id='$info_cb' type='checkbox'>";
  }
  
  echo "<div class='label-box'>";
  echo "<input type='hidden' name='$name' value=0>";
  echo "<input id='$id' type='checkbox' name='$name' value=1 $checked>";
  echo "<label for='$id'>$label</label>";

  if($info)
  {
    $info_link = "ttt-$name-info";
    $icon_url = img_uri('icons8-info.png');
    $info_icon = "<img src='$icon_url' width=18 height=18>";
    // close out the label-box with the info trigger
    echo "<label for='$info_cb' class='info-trigger'>$info_icon</a>";
    echo "</div>";

    // start the info-box
    echo "<div id='$info_link' class='info-box'>";
    echo "<div class='info'><p>$info</p></div>";
  }
  echo "</div>"; // label-box (if no-info) or info-box (if info present)
  echo "</div>"; // input box
}

function add_login_submit($label,$action,$cancel=False)
{
  echo "<!-- Button bar-->";
  if($cancel)
  {
    echo "<div class='submit-bar'>";
    echo "<button class='submit' name='action' value='$action'>$label</button>";
    echo "<button class='cancel' name='action' value='cancel' formnovalidate>Cancel</button>";
    echo "</div>";
  }
  else
  {
    echo "<div class='submit-bar'>";
    echo "<button type='submit' class='submit full' name='action' value='$action'>$label</button>";
    echo "</div>";
  }
}

function add_login_links($links)
{
  $form_uri = app_uri();

  echo "<div class='links-bar'>";
  foreach($links as $link)
  {
    [$label,$page,$side] = $link;
    $page_uri = "$form_uri?p=$page";
    echo "<div class='$side $page'><a href='$page_uri'>$label</a></div>";
  }
  echo "</div>";
}

