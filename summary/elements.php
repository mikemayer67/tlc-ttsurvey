<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));

function start_summary_page($kwargs)
{
  start_header($kwargs['title']);
  add_tab_name('ttt_summary');
  add_js_resources('summary');
  add_css_resources('summary');
  end_header();

  add_navbar('summary',$kwargs);

  start_body();
}

function add_navbar_center_summary($kwargs)
{
  echo '<b>Summary of Responses</b>';
}
