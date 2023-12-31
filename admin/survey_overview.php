<?php
namespace TLC\TTSurvey;

if(!defined('WPINC')) { die; }

if( !plugin_admin_can('view') ) { wp_die('Unauthorized user'); }

require_once plugin_path('include/const.php');
require_once plugin_path('include/settings.php');
require_once plugin_path('include/surveys.php');
require_once plugin_path('include/logger.php');


echo "<div class=overview>";
echo "<h2>Survey Settings</h2>";
add_tlc_settings_overview();
add_tlc_survey_usage();
echo "</div>";

function add_tlc_settings_overview()
{
  echo "<table>";
  add_current_survey_overview();
  add_past_survey_overview();
  add_admins_overview();
  add_survey_url();
  add_advanced();
  echo "</table>";
}

function add_current_survey_overview()
{
  $current = current_survey();

  if($current) {
    $name = $current->name();
    $status = $current->status();
  } else {
    $name = "None";
    $status = "(create/reopen one on the Content tab)";
  }

  echo "<tr>";
  echo "  <td class='label'>Current Survey</td>";
  echo "  <td class='value'>";
  echo "    <table>";
  echo "      <tr>";
  echo "        <td class='name'>$name</td>";
  echo "        <td class='value'>$status</td>";
  echo "      </tr>";
  echo "    </table>";
  echo "  </td>";
  echo "</tr>";
}

function add_past_survey_overview()
{
  echo "<tr>";
  echo "  <td class='label'>Past Surveys</td>";
  echo "  <td class='value'>";
  echo "    <table>";

  $surveys = closed_surveys();
  if($surveys) {
    foreach( $surveys as $survey ) {
      $name = $survey->name();
      $status = $survey->status();
      echo "<tr><td class='name'>$name</td><td class='value'>$status</td></tr>";
    }
  } else {
    echo "<tr><td class='name'>n/a</td></tr>";
  }

  echo "    </table>";
  echo "  </td>";
  echo "</tr>";
}

function add_admins_overview()
{
  echo "<tr>";
  echo "  <td class='label'>Admins</td>";
  echo "  <td class='value'>";
  echo "    <table>";

  $caps = survey_capabilities();
  $primary_admin = survey_primary_admin();

  foreach(get_users() as $user) {
    $id = $user->ID;
    $name = $user->display_name;
    $user_caps = array();
    if( $id == $primary_admin ) { $user_caps[] = 'Primary'; }
    if( $caps['manage'][$id] ?? false ) { $user_caps[] = "Manage"; }
    if( $caps['content'][$id] ?? false ) { $user_caps[] = "Content"; }
    if( $caps['responses'][$id] ?? false ) { $user_caps[] = "Responses"; }
    if( $caps['tech'][$id] ?? false ) { $user_caps[] = "Tech"; }
    if( $caps['data'][$id] ?? false ) { $user_caps[] = "Data"; }
    if( !empty($user_caps) ) {
      $user_caps = implode(", ",$user_caps);
      echo "<tr>";
      echo "<td class='name'>$name</td>";
      echo "<td class='value'>$user_caps</td>";
      echo "</tr>";
    }
  }
  echo "    </table>";
  echo "  </td>";
  echo "</tr>";

}

function add_survey_url()
{
  $pdf_uri = survey_pdf_uri();
  echo "<tr>";
  echo "  <td class='label'>Survey URL</td>";
  echo "  <td class='value'>$pdf_uri</td>";
  echo "</tr>";
}


function add_advanced()
{
  $log_level = LOGGER_[survey_log_level()];
  $survey_post_ui = POST_UI_[survey_post_ui()];
  $user_post_ui = POST_UI_[user_post_ui()];
  echo "<tr>";
  echo "  <td class='label'>Log Level</td>";
  echo "  <td class='value'>$log_level</td>";
  echo "</tr><tr>";
  echo "  <td class='label'>Survey Post UI</td>";
  echo "  <td class='value'>$survey_post_ui</td>";
  echo "</tr><tr>";
  echo "  <td class='label'>User Post UI</td>";
  echo "  <td class='value'>$user_post_ui</td>";
  echo "</tr>";
}


function add_tlc_survey_usage() { ?>

<h2>Usage</h2>
<div class='usage'>

<div class='note'>
Simply add the shortcode <code>[tlc-ttsurvey]</code> to your pages or posts to embed
the Time &amp; Talent survey
</div>

<div class='qual'>
Only the first occurance of this shortcode on any given page will be rendered.  All others will be quietly ignored.
</div>

<div class='note'>
The following <b>optional</b> arguments are currently recognized (<i>yes, there's only one right now</i>):
</div>
<div class='qual'>
Any unspecified argument defaults to the value defined in the plugin settings
</div>

<div class='args'>
<div class='note'>name</div>
<div class='qual'>Must match one of the survey names.</div>
</div>

<div class='note'>Example</div>
<div class='example'><span>[tlc-ttsurvey name=2023]</span></div>

</div>

<h2>Theme Compatibility</h2>
<div class='info'>
  The survey does not render well when its width is too narrow.  If your theme 
  provides wide page templates, you may want to make sure the page that contains 
  the survey uses that template.  Similarly, you probably do not want to use 
  multi-column templates or templates with side bars for the survey page.
</div>


<?php }
