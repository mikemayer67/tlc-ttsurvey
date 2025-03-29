<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

function start_admin_page($tab)
{
  add_admin_navbar($tab);
  print("<div class='body'>");
}

function end_admin_page()
{
  print("</div>");
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

function add_hidden_input($name,$value)
{
  echo "<input type='hidden' name='$name' value='$value'>";
}

