<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

class IssueReporter
{
  private static ?IssueReporter $_instance = null;

  private ?string $_issue_url = null;
  private ?string $_app_id = null;
  private ?string $_install_id = null;
  private ?string $_private_key = null;

  private function __construct() {
    $config = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true);

    $issue_url  = $config['github_issue_url'] ?? null;
    $app_id     = $config['github_app_id'] ?? null;
    $install_id = $config['github_installation_id'] ?? null;
    $key_path   = $config['github_private_key'] ?? null;

    $values = [$issue_url, $app_id, $install_id, $key_path];
    $set_values = count(array_filter($values, fn($v) => $v !== null));
    if($set_values < 4) {
      if ($set_values > 0 ) {
        log_warning("GitHub issue reporting is only partially configured in surveys config file");
      }
      return;
    }

    if( !filter_var($issue_url, FILTER_VALIDATE_URL) ){
      log_error('Invalid github_issue_url in the survey config file');
      return;
    }

    if (!ctype_digit((string)$app_id) || (int)$app_id <= 0) {
      log_error('Invalid github_app_id in the survey config file');
      return;
    }

    if (!ctype_digit((string)$install_id) || (int)$install_id <= 0) {
      log_error('Invalid github_installation_id in the survey config file');
      return;
    }

    $key = file_get_contents($key_path);
    if($key === false) {
        log_error("Invalid github_private_key: cannnot open/read file");
        return;
    }

    if( false === openssl_pkey_get_private($key)) {
      log_error("Invalid github_private_key: content of the file is not a valid key");
      return;
    }

    $this->_issue_url   = $issue_url;
    $this->_app_id      = $app_id;
    $this->_install_id  = $install_id;
    $this->_private_key = $key;
  }

  public static function instance() : IssueReporter
  {
    if( !self::$_instance ) { self::$_instance = new IssueReporter; }
    return self::$_instance;
  }

  public static function configured() : bool 
  {
    return self::instance()->_private_key !== null;
  }
}
