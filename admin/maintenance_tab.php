<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/responses.php'));

$nonce = gen_nonce('admin-maintenance');

$drafts = MySQLFetchAllAssoc('select * from tlc_srv_draft_surveys');

$form_uri = app_uri('admin');
echo "<form id='admin-maintenance' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_input('ajaxuri',app_uri());
add_hidden_submit('action','maintenance');

echo "<div class='section-header'>Unused Database Entries</div>";
echo "<div class='section-content'>";
echo "<button class='maintenance options'>Remove all unused select options</button>";
echo "</div>";

echo "<div class='section-header'>Delete Draft Survey</div>";
echo "<div class='section-content'>";

$select_header = '--Select a survey to delete--';
$empty_header = '--No draft surveys to delete--';
add_hidden_input('select_header',$select_header);
add_hidden_input('empty_header',$empty_header);
$header = $drafts ? $select_header : $empty_header;

echo "<div>";
echo "<select name='draft-surveys' class='draft-surveys'>";
echo "<option value=''>$header</option>";
foreach ($drafts as $draft) {
  $id = $draft['survey_id'];
  $title = $draft['title'];
  $created = $draft['created'];
  $modified = $draft['modified'];
  $responses = count(get_all_responses($id));
  echo "<option value='$id' data-created='$created' data-modified='$modified' data-responses='$responses'>$title</option>";
}
echo "</select>";
echo "</div>";
echo "<div class='draft-info'>";
echo "<span class='created'><span class='label'>Created:</span><span class='value'></span></span>";
echo "<span class='modified'><span class='label'>Modified:</span><span class='value'></span></span>";
echo "<span class='responses'><span class='label'>Responses:</span><span class='value'></span></span>";
echo "</div>";
echo "<div class='content-block delete survey'>";
echo "<div>Deleting a survey is immediate and undoable.</div>";
echo "<div class='confirm'>Please confirm by entering the following...</div>";
echo "<input class='confirm survey' placeholder='I understand and confirm deletion'></input>";
echo "</div>";
echo "<div class='content-block delete responses'>";
echo "<div>All submitted responses to this survey will be lost.</div>";
echo "<div class='confirm'>Please confirm by entering the following...</div>";
echo "<input class='confirm responses' placeholder='I understand responses will be lost'></input>";
echo "</div>";
echo "<button class='maintenance survey'>Delete draft survey</button>";
echo "</div>";

echo "</div>";
echo "</form>";
echo "<script src='", js_uri('maintenance','admin'), "'></script>";
echo "<script src='", js_uri('dayjs.min'), "'></script>";
