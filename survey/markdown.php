<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('vendor/autoload.php'));
use League\CommonMark\CommonMarkConverter;
use HTMLPurifier;
use HTMLPurifier_Config;

require_once(app_file('include/logger.php'));

handle_warnings();

class MarkdownParser {
  private static $instance = null;

  private $wrap = true;
  private $converter;
  private $purifier;

  private function __clone() {}

  private function __construct($wrap = true) 
  {
    $this->wrap = $wrap;

    // block the deprecation warnings from showing up in the browser
    ob_start();

    // --- Setup CommonMarkConverter once ---
    $this->converter = new CommonMarkConverter([
      'html_input' => 'allow',
      'allow_unsafe_links' => false,
    ]);

    // --- Setup HTMLPurifier once ---
    $config = HTMLPurifier_Config::createDefault();

    // Extend allowed tags for Markdown
    $def = $config->getHTMLDefinition(true);
    // allow headers
    $def->addElement("h1", 'Block', 'Flow', 'Common');
    $def->addElement("h2", 'Block', 'Flow', 'Common');
    $def->addElement("h3", 'Block', 'Flow', 'Common');
    $def->addElement("h4", 'Block', 'Flow', 'Common');
    // inline elements
    $def->addElement('b', 'Inline', 'Inline', 'Common');
    $def->addElement('i', 'Inline', 'Inline', 'Common');
    $def->addElement('strong', 'Inline', 'Inline', 'Common');
    $def->addElement('em', 'Inline', 'Inline', 'Common');
    // allow block elements
    $def->addElement('p', 'Block', 'Inline', 'Common');
    $def->addElement('pre', 'Block', 'Flow', 'Common');
    $def->addElement('code', 'Inline', 'Inline', 'Common');
    $def->addElement('blockquote', 'Block', 'Flow', 'Common');
    $def->addElement('hr', 'Empty', 'Empty', 'Common');
    // allow list elements (causing errors for now, so commented out)
    //$def->addElement('ul', 'Block', 'ListItem', 'Common');
    //$def->addElement('ol', 'Block', 'ListItem', 'Common');
    //$def->addElement('li', 'ListItem', 'Flow', 'Common');
    // allow _blank target links
    $def->addElement( 'a', 'Inline', 'Inline', 'Common', [
        'href'   => 'URI',
        'target' => 'Enum#_blank'
      ]
    );

    $this->purifier = new HTMLPurifier($config);

    $ob_string = ob_get_clean();
  }

  private function _parse(string $markdown): string 
  {
    ob_start();

    // --- Convert Markdown to HTML ---
    $raw_html = $this->converter->convert($markdown)->getContent();

    // --- Set target to _blank for all <a> tags ---
    $doc = new \DOMDocument();
    @$doc->loadHTML($raw_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    foreach ($doc->getElementsByTagName('a') as $a) {
      $a->setAttribute('target', '_blank');
    }
    $new_tgt_html = $doc->saveHTML();

    // --- Purify the HTML ---
    $clean_html = $this->purifier->purify($new_tgt_html);

    $ob_string = ob_get_clean();

    if($this->wrap) { 
      $clean_html = "<div class='markdown'>$clean_html</div>";
    }

    // --- Return the converted/sanitized markdown -> HTML ---
    return $clean_html;
  }

  public static function parse(string $markdown, $wrap=true): string
  {
    if(self::$instance === null) {
      self::$instance = new MarkdownParser($wrap);
    }
    return self::$instance->_parse($markdown);
  }
}
