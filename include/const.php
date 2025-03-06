<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

const APP_NAME = 'tlc-ttsurvey';

const SETTINGS_FILE = "admin/settings.json";
const LOG_LEVEL_KEY = "log_level";
const TIMEZONE_KEY = "timezone";

const LOGGER_FILE = "tlc-ttsurvey.log";
const LOGGER_PREFIX = array( "ERROR", "WARNING", "INFO", "DEV" );
const LOGGER_ERR  = 0;
const LOGGER_WARN = 1;
const LOGGER_INFO = 2;
const LOGGER_DEV  = 3;

