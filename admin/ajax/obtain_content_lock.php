<?php
namespace TLC\TTSurvey;

if(!defined('WPINC')) {die;}

require_once plugin_path('admin/content_lock.php');

$lock = obtain_content_lock();

$rval = json_encode($lock);
log_dev($rval);
echo $rval;
wp_die();
