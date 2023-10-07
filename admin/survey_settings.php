<?php
namespace TLC\TTSurvey;

if( !current_user_can('manage_options') ) { wp_die('Unauthorized user'); }

require_once plugin_path('include/settings.php');
require_once plugin_path('include/surveys.php');

$action = explode('?',$_SERVER['REQUEST_URI'])[0].'?'.http_build_query(array(
  'page'=>SETTINGS_PAGE_SLUG,
  'tab'=>'overview',
));

$current_status = current_survey()['status'] ?? null;
?>

<form id='tlc-ttsurvey-settings' class='tlc' action='<?=$action?>' method="POST">
  <input type="hidden" name="action" value="update">
  <?php wp_nonce_field(OPTIONS_NONCE); ?>
  <div class=tlc>

  <div class=label>Survey Status</div>

<?php
if($current_status == SURVEY_IS_DRAFT) { 
?>
  <div class=info>Changing the status from Draft to Active will open the survey.</div>
  <div class=info>Once active, changing back to Draft may invalidate any responses already received.</div>
  <div class=settings>
    <span class='input-label'>Status</span>
    <select name=survey_status class='tlc'>
      <option value=<?=SURVEY_IS_DRAFT?> selected>Draft</option>
      <option value=<?=SURVEY_IS_ACTIVE?>>Active</option>
    </select>
  </div>

<?php 
} elseif($current_status == SURVEY_IS_ACTIVE) { 
?>
  <div class=info>Changing the status from Active to Closed will close the survey.</div>
  <div class=warning>Changing the status back to Draft may result in corruption of  
  responses already received.</div>
  <div class=settings>
    <span class='input-label'>Status</span>
    <select name=survey_status class='tlc'>
      <option value=<?=SURVEY_IS_DRAFT?>>Draft</option>
      <option value=<?=SURVEY_IS_ACTIVE?> selected>Active</option>
      <option value=<?=SURVEY_IS_CLOSED?>>Closed</option>
    </select>
  </div>

<?php 
} else { 
?>

  <div class=info>
    There is no active or draft survey. Create/reopen one in the Content tab.
  </div>

<?php } ?>

  <div class=label>Survey Admins</div>
  <table id='tlc-ttsurvey-admin-caps' class='tlc settings'>
  <tr>
    <th></th>
    <th>Responses</th>
    <th>Content</th>
  </tr>
<?php
$caps = survey_capabilities();
foreach(get_users() as $user) {
  $id = $user->id;
  $name = $user->display_name;
  $response = $caps['responses'][$id] ? "checked" : "";
  $content = $caps['content'][$id] ? "checked" : "";
?>
  <tr>
    <td class=name><?=$name?></td>
    <td><div class=cap>
    <input type=checkbox value=1 name="caps[responses][<?=$id?>]" <?=$response?>>
    </div></td>
    <td><div class=cap>
    <input type=checkbox value=1 name="caps[content][<?=$id?>]" <?=$content?>>
    </div></td>
  </tr>
<?php } ?>
  </table>

<?php
  $pdf_uri = survey_pdf_uri();
?>
  <div class=label>Survey Download URL</div>
  <div class=info>Location for a downloadable copy of the survey</div>
  <input type='URL' class='tlc settings' size=50 name='pdf_uri' value='<?=$pdf_uri?>'
   pattern='^(http|https|ftp|ftps)://[a-zA-Z].*$'>

<?php
  $log_level = survey_log_level();
?>
  <div class=label>Log Level</div>
  <div class=settings>
  <select name='log_level' class='tlc'>
<?php foreach(array("DEV","INFO","WARNING","ERROR") as $log_level) {
  $selected = ($log_level == survey_log_level()) ? "selected" : "";
  echo "<option value=$log_level $selected>$log_level</option>";
  }
?>
  </select>
  </div>
  </div>

  <input type="submit" value="Save" class="submit button button-primary button-large">
</form>