<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/ttpdf_config.php'));
require_once(app_file('pdf/ttpdf.php'));
require_once(app_file('pdf/survey/root_box.php'));

use DateTime;

class SurveyPDF extends TTPDF
{
  /**
   * constructor
   * @param string $title 
   * @return void 
   */
  public function __construct(string $title)
  {
    parent::__construct(
      title:$title,
      subject:"Printable version of the online survey form"
    );
    $this->header_config['logo_size'] = 3*K_EIGHTH_INCH;
    $this->header_config['title_fontsize'] = 20;
    $this->header_config['include_name_field'] = true;
  }

  // The following design note is now OBE, but the refactoring would be a pain.
  //   The initial define, layout, render approach remains.
  //
  // The render design is being driven by the fact that while we can know which
  //   page we're on when rendering each page, we also need to know the number of
  //   pages we will be rendering.  Normally PDF places a placeholder for the page
  //   information... but that makes right justification of the page number in the
  //   footer problematic.  This design allows us to know the page information while
  //   rendering each page and thus format the footer more cleanly.
  //   (yes, this is a silly detail... but easy enough to handle)

  /** Renders the PDF file given the survey content
   * @param array $info contains title, status, etc. about the survey being rendered
   * @param array $content (not even going to attempt to define it here)
   */
  public function render(array $info,array $content)
  {
    $modified = new DateTime($info['modified']);
    $version  = $modified->format('Y.m.d');
    $this->footer_config['timestamp'] = "version: $version";

    $content_root = new SurveyRootBox($this, $content);
    $content_root->layoutChildren($this);
    $this->page_count = PDFBox::numPages();
    $content_root->render($this);
  }
}
