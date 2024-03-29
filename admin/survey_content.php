<?php
namespace TLC\TTSurvey;

if(!defined('WPINC')) { die; }

if( !plugin_admin_can('view') ) { wp_die('Unauthorized user'); }
if( !plugin_admin_can('content') ) { 
  echo "<h2>oops... you shouldn't be here</h2>";
  return;
}

const FIRST_TAB = 'first';

require_once plugin_path('include/const.php');
require_once plugin_path('include/surveys.php');
require_once plugin_path('include/sendmail.php');
require_once plugin_path('admin/content_lock.php');

add_noscript_body();
add_script_body();

function add_noscript_body()
{
  echo "<noscript class='warning'>";
  echo "<p>Managing survey content requires that Javascript be enabled</p>";
  echo "</noscript>";
}

function add_script_body()
{
  echo "<div class='content requires-javascript'>";

  // check to see if we have lock
  $lock = obtain_content_lock();
  if($lock['has_lock']) {
    // we have the lock
    $active_pid = determine_content_tab();
    add_survey_navbar($active_pid);
    add_survey_tab_content($active_pid);
  } else {
    // someone else has lock
    add_content_lock($lock);
  }
  echo "</div>";
}


function determine_content_tab()
{
  // returns the post_id of the selected content tab
  $current = current_survey();

  // if post_id (pid) was specified as part of the GET request, honor it
  //   note that if value of pid is 'first', we need to resolve that to
  //   the current post_id if there is a current survey
  $pid = $_GET['pid'] ?? null;
  if($pid)
  {
    if($pid == FIRST_TAB) { 
      return $current ? $current->post_id() : FIRST_TAB;
    } else {
      return $pid;
    }
  }

  // no pid was specified as part of the GET request.
  //   Show the current survey if there is one
  if($current) { return $current->post_id(); }

  // no pid specified and no current survey
  //   Show the newest entry in the survey catalog
  $closed = closed_surveys();
  if($closed) { return $closed[0]->post_id(); }

  // no pid specified, no current survey, and no survey catalog
  //   Only option is to create a new survey (i.e. first and only tab)
  return FIRST_TAB;
}


function add_survey_navbar($active_pid)
{
  echo "<div class='nav-tab-wrapper survey'>";

  $query_args = array();
  $uri_path = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
  parse_str(parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY),$query_args);

  // construct array of tabs
  $tabs = array();

  //   first tab is current survey if there is a current survey
  //     otherwise, it's the new survey tab
  $current = current_survey();

  if($current) {
    $tabs[] = array($current->name(),$current->post_id());
  } else {
    $tabs[] = array(' + ',FIRST_TAB);
  }

  // remaining tabs come from the survey catalog (skipping current survey)
  foreach( closed_surveys() as $survey ) {
    $tabs[] = array($survey->name(), $survey->post_id());
  }

  // populate the tabs
  foreach($tabs as $tab)
  {
    [$label,$pid] = $tab;
    $active = ($pid == $active_pid) ? 'nav-tab-active' : '';
    $query_args['pid'] = $pid;
    $uri = implode('?', array($uri_path,http_build_query($query_args)));
    echo "<a class='pid nav-tab $active' href='$uri'>$label</a>";
  }

  echo "</div>";
}


function add_survey_tab_content($active_pid)
{
  $current = current_survey();

  $current_pid = $current ? $current->post_id() : '';

  if($active_pid == FIRST_TAB) {
    add_new_survey_content();
  } elseif( $active_pid == $current_pid ) {
    add_current_survey_content();
  } else {
    add_past_survey_content($active_pid);
  }
}

function add_new_survey_content()
{
  $existing_names = survey_catalog()->survey_names();

  $cur_year = date('Y');
  $suggested_name = "$cur_year";
  $n = 2;
  while(in_array($suggested_name,$existing_names))
  {
    $suggested_name = "$cur_year-$n";
    ++$n;
  }
  $existing_names = json_encode($existing_names);

  $candidate_parents = SurveyCatalog::instance()->survey_name_index();
  krsort($candidate_parents);

  echo "<div class=new>";
  echo "  <h2>Create a New Survey</h2>";
  echo "  <form class='new-survey'>";
  echo "    <input class='existing' type='hidden' value='$existing_names'>";
  echo "    <table>";
  echo "      <tr>";
  echo "        <td class='label'>Survey Name:</td>";
  echo "        <td class='value'>";
  echo "          <input type='text' class='new-name' name='name' value='$suggested_name'>";
  echo "          <span class='error'></span>";
  echo "        </td>";
  echo "      </tr>";
  if($candidate_parents) {
    echo "      <tr>";
    echo "        <td class='label'>Clone from:</td>";
    echo "        <td class='value'>";
    echo "          <select class='select-parent' name='parent'>";
    echo "            <option value='0'>None</option>";
    foreach ($candidate_parents as $pid=>$name) {
      echo "            <option value='$pid'>$name</option>";
    }
    echo "          </select>";
    echo "        </td>";
    echo "      </tr>";
  } else {
    echo "    <input class='select-parent' type='hidden' value='0'>";
  }
  echo "    </table>";
  echo "    <div class='button-box'>";
  $class = 'submit button button-primary button-large';
  echo "      <input type='submit' value='Create Survey' class='$class''>";
  echo "    </div>";
  echo "  </form>";
  echo "</div>";

  enqueue_new_survey_javascript();
}

function add_past_survey_content($pid)
{
  $survey = survey_catalog()->lookup_by_post_id($pid);

  if(!$survey) { 
    log_error("Attempted to show content for invalid pid ($pid)");
    return;
  }

  echo "<div class='past'>";

  $current = current_survey();

  if(!$current) {
    echo "<form class='reopen-survey'>";
    echo "<input type='hidden' name='pid' value='$pid'>";
    echo "<input type='submit' value='Reopen survey'>";
    echo "</form>";

    enqueue_reopen_javascript();
  }

  $name = $survey->name();
  echo "<div class='info'>";
  echo "<div> The $name Time and Talent Survey is currently closed. ";
  echo "</div><div>";
  echo "No changes can be made to its content.";
  echo "</div></div>";

  add_survey_content($survey);

  echo "</div>";
}

function add_current_survey_content()
{
  $survey = current_survey();

  echo "<div class='current'>";

  $name = $survey->name();;
  if($survey->is_active()) {
    echo "<div class='info'>";
    echo "<div> The $name Time and Talent Survey is currently open. ";
    echo "</div><div>";
    echo "No changes can be made to its content without moving it back ";
    echo "to Draft status on the Settings tab.";
    echo "</div></div>";
    $editable = false;
  }
  elseif($survey->is_draft()) {
    echo "<div class='info'>";
    echo "<div>The $name Time and Talent Survey is currently in draft mode.";
    echo "</div><div>";
    echo "To lock in its structure and open it for participation, switch its status";
    echo " to Active on the Settings tab.";
    echo "</div></div>";
    $editable = true;
  }
  else {
    log_error("Attempting to add closed survey as current");
    wp_die("Internal error, contact Time & Talent plugin author if this persists");
  }

  // revisions note
  $url = admin_url() . "edit.php?post_type=" . SURVEY_POST_TYPE;
  echo "<div class='info revisions'`>";
  echo "Revision tracking is handled via the survey <a href='$url'>post editor</a>";
  echo "</div>";

  add_survey_content($survey,$editable);

  echo "</div>";
}


function add_survey_content($survey,$editable=false)
{
  $name = $survey->name();
  $pid = $survey->post_id();
  $last_modified = $survey->last_modified();

  // wrap the content in a form
  //   no action/method as submision will be handled by javascript
  if($editable)
  {
    echo "<form class='content edit'>";
  } else {
    echo "<form class='content no-edit'>";
  }
  echo "<input type='hidden' name='pid' value='$pid'>";
  // last modified will be filled in by javascript
  echo "<input type='hidden' name='last-modified' value=0>";

  //
  // Add the block navbar
  //

  $active_block = $_GET['block'] ?? 'survey';
  echo "<div class='nav-tab-wrapper block'>";
  echo "<input type='hidden' name='active_block' value='$active_block'>";
  $blocks = [['survey','Survey Form'],['sendmail','Email Customization']];
  foreach( $blocks as [$key,$label] ) {
    $class = 'block nav-tab';
    if($key == $active_block) { $class = "$class nav-tab-active"; }
    echo "<a class='$class' data-target='$key'>$label</a>";
  }
  echo "</div>"; // nav-tab-wrapper.blocks

  // 
  // Add actual form content
  //
  echo "<div class=content-block>";

  // Survey 

  echo "<div class='block survey'>";
  echo "<div class='info'>";
  echo "Instructions go here.";
  echo "</div>";
  echo "<textarea class='survey' name='survey' readonly></textarea>";
  echo "<div class='invalid survey'></div>";
  echo "</div>";

  // Email Customization

  echo "<div class='block sendmail'>";

  echo "<div class='info'>";
  echo "Email customization defines the (optional) custom content to include ";
  echo "within the body of email that is sent to survey participants. ";
  echo "It does not affect the content that is generated by the plugin code.";
  echo "</div>";

  echo "<div class='info'>";
  echo "All email customization accepts markdown notation.  For more information, visit ";
  echo "the <a href='https://www.markdownguide.org/basic-syntax' target='_blank'>";
  echo "Markdown Guide</a>. As markdown inherently recognizes html, email customization ";
  echo "also html markup and just plain text.";
  echo "</div>";

  echo "<div class='info'>";
  echo "Note that the email message may appear differently in the recipient's ";
  echo "email application as it may render the sent html differently than the ";
  echo "browser you are currently using.";
  echo "</div>";

  foreach(SENDMAIL_TEMPLATES as $key=>$template) {
    $label = $template['label'] ?? ucfirst($key);
    $when = $template['when'];
    echo "<!-- $label -->";
    echo "<div class='sendmail $key'>";
    echo "<h3>$label</h3>";
    echo "<div class='sendmail info'>Sent when $when</div>";
    echo "<textarea class='sendmail $key' name='$key' readonly></textarea>";
    echo "<div class='sendmail preview $key'></div>";
    echo "</div>";
  }

  echo "</div>"; // block sendmail

  //
  // close out the form
  //   add submit button if editable
  //
  if($editable) {
    echo "<div class='button-box'>";
    echo "<input type='submit' class='submit button button-primary button-large' value='Save' disabled>";
    echo "<button class='revert button button-secondary button-large' name='revert' disabled>revert all updates</button>";
    echo "</div>";
  }

  echo "</form>";

  //
  // enqueue the javascript
  //
  enqueue_content_javascript($editable);
}


function enqueue_content_javascript($editable)
{
  wp_register_script(
    'tlc_ttsurvey_content_form',
    plugin_url('admin/js/content_form.js'),
    array('jquery'),
    '1.0.3',
    true
  );
  wp_localize_script(
    'tlc_ttsurvey_content_form',
    'form_vars',
    array(
      'ajaxurl' => admin_url( 'admin-ajax.php' ),
      'nonce' => array('content_form',wp_create_nonce('content_form')),
      'editable' => $editable,
    ),
  );
  wp_enqueue_script('tlc_ttsurvey_content_form');
}

function enqueue_reopen_javascript()
{
  wp_register_script(
    'tlc_ttsurvey_reopen_form',
    plugin_url('admin/js/reopen_form.js'),
    array('jquery'),
    '1.0.3',
    true
  );
  wp_localize_script(
    'tlc_ttsurvey_reopen_form',
    'reopen_vars',
    array(
      'ajaxurl' => admin_url( 'admin-ajax.php' ),
      'nonce' => array('reopen_form',wp_create_nonce('reopen_form')),
      'content_url' => $_SERVER['REQUEST_URI'],
    ),
  );
  wp_enqueue_script('tlc_ttsurvey_reopen_form');
}

function enqueue_new_survey_javascript()
{
  wp_register_script(
    'tlc_ttsurvey_new_survey_form',
    plugin_url('admin/js/new_survey_form.js'),
    array('jquery'),
    '1.0.3',
    true
  );
  wp_localize_script(
    'tlc_ttsurvey_new_survey_form',
    'form_vars',
    array(
      'ajaxurl' => admin_url( 'admin-ajax.php' ),
      'nonce' => array('new_survey_form',wp_create_nonce('new_survey_form')),
    ),
  );
  wp_enqueue_script('tlc_ttsurvey_new_survey_form');
}

