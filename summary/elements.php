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
  add_notebook_css($kwargs['tab_ids']);
  end_header();

  add_navbar('summary',$kwargs);


  start_body();
}

function add_navbar_center_summary($kwargs)
{
  echo '<b>Summary of Responses</b>';
}

function add_navbar_right_summary($kwargs)
{
  add_return_to_survey();
}

function add_notebook_css($tab_ids)
{
  echo "<style>\n";
  foreach($tab_ids as $id) {
    echo "#tab-cb-$id:checked ~ #panel-$id { display:block; }\n";
  }

  echo implode(
    ",\n", 
    array_map( function($id) { return "#tab-cb-$id:checked ~ div.tabs label.tab-$id"; }, $tab_ids )
  );
  echo "{";
  echo " background:#f4f4f4;";
  echo " border-bottom: solid #f4f4f4 2px;";
  echo "}";
  
  echo "</style>";
}
