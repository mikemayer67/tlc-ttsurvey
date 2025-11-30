<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));

/****************************************************************
 **
 ** Common Look and Feel for all login forms
 **
 ****************************************************************/

function start_login_page()
{
  $context = 'login';
  start_header();
  add_tab_name('ttt_survey');
  add_js_resources($context);
  add_css_resources($context);
  end_header();
  add_navbar($context);
  add_js_recommended();
  add_status_bar();
  start_body();
}

function start_login_form($header,$name) 
{
  $form_uri = app_uri();
  $nonce = gen_nonce($name);

  $q = '';
  if($name === 'admin') {
    $q = "?admin";
  }

  echo "<div id='ttt-login' class='$name'>";
  echo "<div class='$name ttt-card'>";
  echo "<header>$header</header>";
  echo "<form class='login' method='post' action='$form_uri$q'>";
  add_hidden_input('nonce',$nonce);
  add_hidden_input('form',$name);
  return $nonce;
}

function close_login_form()
{
  // must close all DOM elements opened in ttt-login
  $icon_url = img_uri('icons8/info.png');
  $info_icon = "<img src='$icon_url'>";

  echo "</form>";
  echo "</div>";  // ttt-card
  echo "<div class='help'>For hints on using this form, click or hover on any of the $info_icon icons</div>";
  echo "</div>";  // ttt-card-wrapper
}

function add_resume_buttons($nonce)
{
  $tokens = access_tokens();
  if(!$tokens) { return; }
  
  $icon = img_uri('icons8/delete_sign.png');
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
  $placeholder = $kwargs['placeholder'] ?? '';
  $info = $kwargs['info'] ?? null;
  $id = "ttt-input-$name";

  echo "<!-- $label -->";
  echo "<div class='input $name'>";

  if($info) {
    $info_cb = "ttt-$name-info-cb";
    echo "<input id='$info_cb' class='info-cb $name' type='checkbox'>";
  }

  # add label box

  echo "<div class='label-box'>";
  echo "<label for='$id'>$label</label>";
  if($info) { 
    $info_link = "ttt-$name-info";
    $icon_url = img_uri('icons8/info.png');
    $info_icon = "<img src='$icon_url'>";
    echo" <label for='$info_cb' class='info-trigger'>$info_icon</label>";
  }
  echo "<div class='error $name'></div>";
  echo "</div>"; // label-box

  # add input fields
  
  $optional = $optional ? 'optional' : '';
  $required = $optional ? "placeholder='[optional] $placeholder'" : 'required';
  $ac = "autocomplete='$type'";
  
  if($type=='locked')
  {
    add_hidden_input($name,$value);
    $value = $placeholder ?: $value;
    echo "<div class='locked-input'>$value</div>";
  }
  elseif($type=='new-password') 
  {
    $classes = 'text-entry entry $optional';
    $attributes = "required $ac placeholder='new password'";
    echo "<input id='$id' type='password' class='$classes primary' name='$name'         $attributes>";
    $attributes = "required $ac placeholder='retype password'";
    echo "<input          type='password' class='$classes confirm' name='$name-confirm' $attributes>";
  }
  else
  {
    $value = $value ? "value=\"$value\"" : "";

    switch($type) {
    case 'password': $value = '';    break;
    case 'email':                    break;
    default:         $type = 'text'; break;
    }
    $attributes = "$value $required $ac placeholder='$placeholder'";
    echo "<input id='$id' type='$type' class='text-entry $optional' name='$name' $attributes>";
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
    echo "<input id='$info_cb' class='info-cb $name' type='checkbox'>";
  }
  
  echo "<div class='label-box'>";
  echo "<input type='hidden' name='$name' value=0>";
  echo "<input id='$id' type='checkbox' name='$name' value=1 $checked>";
  echo "<label for='$id'>$label</label>";

  if($info)
  {
    $info_link = "ttt-$name-info";
    $icon_url = img_uri('icons8/info.png');
    $info_icon = "<img src='$icon_url' width=18 height=18>";
    // close out the label-box with the info trigger
    echo "<label for='$info_cb' class='info-trigger'>$info_icon</label>";
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
    echo "<div class='$side $page'><a href='$page_uri' target='ttt_survey'>$label</a></div>";
  }
  echo "</div>";
}

function add_login_instructions($instructions)
{
  echo "<div>";
  foreach($instructions as $instruction) {
    echo "<div class='instruction'>$instruction</div>";
  }
  echo "</div>";
}

