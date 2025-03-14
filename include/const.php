<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

const APP_NAME = 'tlc-ttsurvey';

// Admin Settings
const SETTINGS_FILE = "admin/settings.json";
const LOG_LEVEL_KEY = "log_level";

function load_config() {
  $config = parse_ini_file(APP_DIR."/tlc-ttsurvey.ini",true);

  define('NAVBAR_LOGO',   $config['content']['navbar_logo'] ?? null);
  define('DEFAULT_TITLE', $config['content']['default_title'] ?? "Time and Talent Survey");

  define('MYSQL_USERID',  $config['mysql']['userid']);
  define('MYSQL_PASSWORD',$config['mysql']['password']);
  define('MYSQL_SCHEMA',  $config['mysql']['schema']);
  define('MYSQL_HOST',    $config['mysql']['host']);
  define('MYSQL_CHARSET', $config['mysql']['charset'] ?? 'utf8');

  define('LOGGER_FILE',   $config['logging']['log_file'] ?? null);
  define('LOGGER_TZ',     $config['logging']['log_tz'] ?? 'UTC');

  define('ADMIN_NAME',    $config['admin']['name'] ?? "the survey admin");
  define('ADMIN_EMAIL',   $config['admin']['email'] ?? null);
  define('ADMIN_PRONOUN', $config['admin']['pronoun'] ?? "them");
};
load_config();

if(ADMIN_EMAIL) {
  define('ADMIN_CONTACT', sprintf("<a href='mailto:%s'>%s</a>",ADMIN_EMAIL,ADMIN_NAME));
} else {
  define('ADMIN_CONTACT', ADMIN_NAME);
}

