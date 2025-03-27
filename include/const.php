<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

const PKG_NAME = 'tlc-ttsurvey';

// Admin Settings
const SETTINGS_FILE = "admin/settings.json";
const LOG_LEVEL_KEY = "log_level";

function load_config() {
  $config = parse_ini_file(APP_DIR."/tlc-ttsurvey.ini",true);

  define('APP_NAME',        $config['main']['name']     ?? "Time and Talent Survey" );
  define('APP_LOGO',        $config['main']['logo']     ?? null                     );
  define('APP_TZ',          $config['main']['timezone'] ?? "UTC"                    );

  define('ADMIN_NAME',      $config['admin']['name']    ?? "the survey admin" );
  define('ADMIN_EMAIL',     $config['admin']['email']   ?? null               );
  define('ADMIN_PRONOUN',   $config['admin']['pronoun'] ?? "them"             );

  define('PWRESET_TIMEOUT', $config['security']['pwreset_timeout'] ?? 900 );
  define('PWRESET_LENGTH',  $config['security']['pwreset_length']  ?? 10  );

  define('LOG_FILE',        $config['logging']['filename'] ?? (PKG_NAME.".log") );
  define('LOG_LEVEL',       $config['logging']['level']    ?? 2                 );

  define('MYSQL_USERID',    $config['mysql']['userid']);
  define('MYSQL_PASSWORD',  $config['mysql']['password']);
  define('MYSQL_SCHEMA',    $config['mysql']['schema']);
  define('MYSQL_HOST',      $config['mysql']['host']);
  define('MYSQL_CHARSET',   $config['mysql']['charset'] ?? 'utf8');

  define('SMTP_HOST',       $config['smtp']['host'] ?? null);
  define('SMTP_AUTH',       $config['smtp']['auth'] ?? 1   );
  define('SMTP_PORT',       $config['smtp']['port'] ?? ( SMTP_AUTH == 0 ? 465 : 587 ) );
  define('SMTP_USERNAME',   $config['smtp']['username'] ?? null);
  define('SMTP_PASSWORD',   $config['smtp']['password'] ?? null);
  define('SMTP_REPLY_TO',   $config['smtp']['reply_email'] ?? SMTP_USERNAME );
  define('SMTP_REPLY_NAME', $config['smtp']['reply_email'] ?? null );
  define('SMTP_DEBUG',      $config['smtp']['debug'] ?? 0 );

};
load_config();

if(ADMIN_EMAIL) {
  define('ADMIN_CONTACT', sprintf("<a href='mailto:%s'>%s</a>",ADMIN_EMAIL,ADMIN_NAME));
} else {
  define('ADMIN_CONTACT', ADMIN_NAME);
}

