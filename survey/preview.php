<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Preview --------------");

validate_and_retain_nonce('preview');

require_once(app_file('include/login.php'));
$active_user = active_userid();
log_dev("Active User: $active_user");

$page_title = 'Survey Preview';

$survey_title = $_POST['title'] ?? '[No Name]';
$content = json_decode($_POST['content'] ?? '',true);

require(app_file('survey/render.php'));

die();
