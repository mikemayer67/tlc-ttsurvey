<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('vendor/autoload.php'));
use League\CommonMark\CommonMarkConverter;

handle_warnings();

class MarkdownStripper {
  private static $instance = null;

  private $converter;

  private function __clone() {}

  private function __construct() 
  {
    // block the deprecation warnings from showing up in the browser
    ob_start();

    // --- Setup CommonMarkConverter once ---
    $this->converter = new CommonMarkConverter([
      'allow_unsafe_links' => false,
    ]);

    ob_end_clean();
  }

  private function _strip(string $markdown): string
  {
    ob_start();

    $html = $this->converter->convert($markdown)->getContent();

    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $text = preg_replace('/\s+/', ' ', $text);

    ob_end_clean();

    return trim($text);
  }

  public static function strip(string $markdown): string
  {
    if(self::$instance === null) {
      self::$instance = new MarkdownStripper();
    }
    return self::$instance->_strip($markdown);
  }
};

function strip_markdown($s) {
  return MarkdownStripper::strip($s);
}

