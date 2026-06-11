<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/logger.php'));

class IssueReporter
{
  private static ?IssueReporter $_instance = null;

  private ?string $_issue_url = null;
  private ?string $_install_url = null;
  private ?string $_app_id = null;
  private ?string $_install_id = null;
  private ?string $_private_key = null;

  private function __construct() {
    $config = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true);

    $issue_url   = $config['github_issue_url'] ?? null;
    $install_url = $config['github_install_url'] ?? null;
    $app_id      = $config['github_app_id'] ?? null;
    $install_id  = $config['github_installation_id'] ?? null;
    $key_path    = $config['github_private_key'] ?? null;

    $values = [$issue_url, $install_url, $app_id, $install_id, $key_path];
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

    if( !filter_var($install_url, FILTER_VALIDATE_URL) ){
      log_error('Invalid github_install_url in the survey config file');
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
    $this->_install_url = $install_url;
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

  public function create_issue(string $errid, string $errmsg, string $usermsg, string $reporter) : ?string
  {
    $token = $this->get_install_token();
    if (!$token) { return null; }

    $timestamp = date("d-M-y H:i:s T");

    $title = "User Reported Bug ($errid)";
    $body = "An internal error was reported by $reporter at $timestamp.\n\n";
    $body .= "Error log entry[$errid]: $errmsg\n\n";
    $body .= "User input:\n-----------\n$usermsg";

    $payload = json_encode([
      'title'  => $title,
      'body'   => $body,
      'labels' => ['bug'],
    ]);

    $ch = curl_init($this->_issue_url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $payload,
      CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        "Accept: application/vnd.github+json",
        "User-Agent: tlc-ttsurvey app"
      ]
    ]);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch,CURLINFO_HTTP_CODE);

    if ($status >= 300) {
      log_error("GitHub API error ($status): $response");
      return null;
    }
    $response = json_decode($response,true);
    return $response['html_url'];
  }

  // ---------- JWT ----------

  private function createJWT()
  {
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $now = time();

    $payload = [
      'iat' => $now,
      'exp' => $now + 600,
      'iss' => $this->_app_id,
    ];

    $base64Header = $this->base64url(json_encode($header));
    $base64Payload = $this->base64url(json_encode($payload));

    $data = $base64Header . '.' . $base64Payload;

    openssl_sign($data, $signature, $this->_private_key, 'sha256');

    return $data . '.' . $this->base64url($signature);
  }

  private function base64url(string $data)
  {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  /**
   * Base64 encodes the specified data 
   *   and translates all '+' to '-' and all '/' to '_'
   * @param mixed $data 
   * @return string 
   */
  private static function base64url_encode(string $data) : string 
  {
    $rval = base64_encode($data);
    $rval = strtr($rval, '+/', '-_');
    $rval = rtrim($rval, '=');
    return $rval;
  }

  /**
   * Creates a JSON Web Token (JWT) 
   * @return string JWT
   */
  private function create_jwt() : string
  {
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $header_json = json_encode($header);
    $header_b64  = self::base64url_encode($header_json);

    $now = time();
    $payload = [
        'iat' => $now,
        'exp' => $now + 600, // max 10 minutes
        'iss' => $this->_app_id,
    ];
    $payload_json = json_encode($payload);
    $payload_b64  = self::base64url_encode($payload_json);

    $data = "$header_b64.$payload_b64";

    $signature = null;
    openssl_sign($data, $signature, $this->_private_key, 'sha256');
    $signature_b64 = self::base64url_encode($signature);

    $rval = "$data.$signature_b64";

    return $rval;
  }

  private function get_install_token() : ?string
  {
    $jwt = $this->create_jwt();

    $url = implode('/',[$this->_install_url, $this->_install_id, 'access_tokens']);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $jwt",
        "Accept: application/vnd.github+json",
        "User-Agent: tlc-ttsurvey app"
      ]
    ]);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($status >= 300) {
      log_error("Token fetch failed ($status): $response");
      return null;
    }

    $data = json_decode($response, true);

    return $data['token'];
  }
}
