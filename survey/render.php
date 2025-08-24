<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Render --------------");

handle_warnings();

require_once(app_file('vendor/autoload.php'));
use League\CommonMark\CommonMarkConverter;
use HTMLPurifier;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_ErrorCollector;

require_once(app_file('include/logger.php'));

function render_survey($userid,$content,$kwargs=[])
{
  echo "<form id='survey'>";

  foreach($content['sections'] as $section) {
    add_section($section, $content);
  }

  echo "</form>";
}

function add_section($section,$content)
{
  $sequence    = $section['sequence'];
  $name        = $section['name'];
  $collapsible     = $section['collapsible'] ?? true;
  $description = $section['description'] ?? '';
  $feedback    = $section['feedback'] ?? false;

  if($collapsible) {
    echo "<details class='section $name'>";
    echo "<summary>$name</summary>";
    $closing_tag = "</details>";
  }
  else
  {
    echo "<div class='section $name'>";
    echo $name;
    $closing_tag = "</div>";
  }

  if($description) {
    echo "<div class='description $name'>";
    add_markdown($description);
    echo "</div>";
  }

  if($feedback) {
    echo "<div class='feedback $sequence'>";
    echo "<div class='label'>$feedback</div>";
    echo "<input class='section feedback $sequence' name='section-feedback-$sequence'>";
    echo "</div>";
  }

  $questions = array_filter($content['questions'], 
    function($row) use ($sequence) {
      $section = $row['section'] ?? null;
      if(!array_key_exists('sequence',$row)) { return false; }
      return $section === $sequence;
    }
  );
  usort($questions, function($a,$b) {
    return $a['sequence'] <=> $b['sequence'];
  });

  print "<pre>".print_r($questions,true)."</pre>";

  echo $closing_tag;
}


function add_markdown($markdown)
{
  ob_start();

  $converter = new CommonMarkConverter([ 'html_input' => 'allow' ]);
  $raw_html = $converter->convertToHtml($markdown);
  log_dev("raw_html: ".$raw_html);

  $config = HTMLPurifier_Config::createDefault();
  log_dev('check');
  $config->set('Cache.DefinitionImpl',null);
  log_dev('check');
  $config->set('HTML.Allowed','b,strong,em,i,p,ul,ol,li'); # consider adding a[href]

  log_dev('check');
  $context = new HTMLPurifier_Context();
  log_dev('check');
  $errors = new HTMLPurifier_ErrorCollector($config);
  log_dev('check');

  $purifier = new HTMLPurifier($config);
  log_dev('check');
  $clean_html = $purifier->purify($raw_html);
  log_dev("clean_html: ".$clean_html);

  $ob_string = ob_get_clean();
  if($ob_string) {
    log_dev("OB Warning: $ob_string");
  }

  echo $clean_html;
}

