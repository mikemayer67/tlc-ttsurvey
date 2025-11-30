<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/elements.php'));

function start_survey_page($kwargs)
{
  start_header($kwargs['title']);
  add_tab_name('ttt_survey');

  add_js_resources('survey', js_uri('jquery_helpers'));
  add_css_resources('survey');

  end_header();

  add_navbar('survey', $kwargs);

  add_js_recommended();
  add_status_bar();

  start_body();
}

function add_navbar_center_survey($kwargs)
{
  log_dev("add_navbar_center_survey: ".print_r($kwargs,true));
  $submitted = $kwargs['submitted'] ?? null;
  $draft     = $kwargs['draft']     ?? null;

  if($submitted && $draft) {
    $ts1 = recent_timestamp_string($submitted);
    $ts2 = recent_timestamp_string($draft);
    echo "<div class='survey-status'>";
    echo "<div class='key'>Last Submitted</div><div class='timestamp'>$ts1</div>";
    echo "<div class='key'>Last Saved Draft</div><div class='timestamp'>$ts2</div>";
    echo "</div>";
  }
  elseif($draft) { 
    $ts = recent_timestamp_string($draft);
    echo "<div class='survey-status'>";
    echo "<div class='key'>Submitted</div><div class='timestamp'></div>";
    echo "<div class='key'>Last Saved Draft</div><div class='timestamp'>$ts</div>";
    echo "</div>";
  }
  elseif($submitted) {
   if($kwargs['reopen'] ?? false) {
     $ts = recent_timestamp_string($submitted);
     echo "<div class='survey-status'>";
     echo "<div class='key'>Last Submitted</div><div class='timestamp'>$ts</div>";
     echo "</div>";
   }
  }
  else {
    echo "<b>Welcome</b>";
  }
}

function add_navbar_right_survey($kwargs)
{
  $userid = $kwargs['userid'] ?? null;
  if(!$userid) { return null; }

  $rval = add_user_menu($userid);

  return ['user_menu' => $rval];
}


function start_preview_page($title,$userid,$enable_js=true)
{
  start_header($title);
  add_tab_name('ttt_preview');

  // We want to load the survey css and javascript
  if($enable_js) { 
    add_js_resources('survey', js_uri('jquery_helpers')); 
  }
  add_css_resources('survey');

  end_header();

  // But, we want the navbar to reflect that this is preview context
  add_navbar('preview', [
    'userid' => $userid, 
    'title'  => $title,
  ]);

  add_js_recommended($enable_js ? 'noscript' : 'div');
  add_status_bar();

  start_body();
}

function add_navbar_center_preview($kwargs) {
  echo '<b>Preview</b>';
}


function start_nosurvey_page()
{
  $context = 'survey';

  start_header();
  add_tab_name("ttt_$context");
  

  add_js_resources($context, js_uri('jquery_helpers'));
  add_css_resources($context);

  end_header();

  $userid = active_userid() ?? null;

  add_navbar($context, ['userid'=>$userid]);

  add_js_recommended();

  start_body();
}



