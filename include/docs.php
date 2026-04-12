<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('vendor/autoload.php'));

use League\CommonMark\Environment\Environment;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;

require_once(app_file('include/logger.php'));

class DocsPage
{
  private $_title = '';
  private $_html = '';

  public function title() : string { return $this->_title; }
  public function html()  : string { return $this->_html;  }

  public function __construct(string $topic)
  {
    // Load from file

    $doc_file = app_file("docs/$topic.md");
    $fp = fopen($doc_file,"r");
    if(!$fp) { api_die("Attempted to load doc file $doc_file"); }

    $md = fread($fp,filesize($doc_file));

    fclose($fp);

    // extract title

    $this->_title = $topic; // default if no title found

    $first_line = explode("\n",$md);
    if($first_line) {
      $first_line = trim($first_line[0]);
      if(str_starts_with($first_line,'#')) {
        $this->_title = trim($first_line," #");
      }
    }

    // convert all img links to html <img>
    $n_img_links = 0;
    $md = preg_replace(
      "/!\[([^\]]+)\]\s*\(([^\)]+)\)/",
      '<img src="$2" alt="$1">',
      $md,-1,$n_img_links
    );

    // fix all img links to be withing docs folder
    $n_img = 0;
    $md = preg_replace(
      "/(<img.*?src\s*=\s*['\"])/",
      '${1}docs/',
      $md,-1,$n_img
    );
    
    // convert all md links to html links
    $n_links = 0;
    $md = preg_replace(
      "/\[([^\]]+)\]\s*\(([^\)]+)\)/",
      '<a class="doc-link" href="#" data-topic="$2">$1</a>',
      $md,-1,$n_links
    );

    // convert markdown to html

    $environment = new Environment();
    $environment->addExtension(new GithubFlavoredMarkdownExtension());
    $converter = new CommonMarkConverter([], $environment);

    $this->_html = $converter->convert($md)->getContent();
  }
};