<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));
require_once(app_file('include/question_types.php'));
require_once(app_file('summary/markdown.php'));

/**
 * Starts the HTML for the summary page and opens the body element for
 *   fleshing out its content.
 * 
 * @param array{
 *    title  : string,
 *    userid : string,
 *    tab_ids : list<int>
 * } $kwargs
 */
function start_summary_page(array $kwargs) : void
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

/**
 * 
 * @param array $kwargs 
 * @return void 
 */
function add_navbar_center_summary(array $kwargs) : void
{
  if( $kwargs['title']??null ) {
    echo '<b>Summary of Responses</b>';
  }
}

function add_navbar_right_summary($kwargs)
{
  add_return_to_survey();

  $survey_id = $_REQUEST['summary'];
  if(!$survey_id) { $survey_id = active_survey_id(); }

  add_hidden_input('ajaxurl', app_uri());
  add_hidden_input('survey_id',$survey_id);
  add_hidden_input('nonce',gen_nonce('summary-download'));
  echo "<a id='download-pdf' class='js-only file-download'>Download PDF</a>";
  echo "<a id='download-csv' class='js-only file-download'>Download CSV</a>";
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


function add_section_panel($sid,$content,$responses)
{
  $sp = new SectionPanel($sid,$content,$responses);
  $sp->add();
}


