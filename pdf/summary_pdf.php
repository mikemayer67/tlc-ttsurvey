<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/ttpdf.phpp'));
require_once(app_file('pdf/summary_root_box.php'));

use DateTime;

class SummaryPDF extends TTPDF
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
      subject:"Printable version of the survey summary"
    );
    $this->header_config['logo_size'] = K_HALF_INCH;
    $this->header_config['title_fontsize'] = 16;
    $this->header_config['include_name_field'] = false;
  }

  /**
   * Updates the section string that will appear in the footer
   * @param string $section 
   * @return void 
   */
  public function setSection(string $section)
  {
    $this->footer_config['section'] = $section;
  }

  /**
   * Removes the section string from the footer
   * @return void 
   */
  public function clearSection() {
    $this->footer_config['section'] = null;
  }

  /**
   * Renders the PDF file given the survey content and user responses
   * @param array $info contains title, status, etc. about the survey being rendered
   * @param array $content (not even going to attempt to define it here)
   * @param array $responses (not even going to attempt to define it here)
   * @return void 
   */
  public function render(array $info, array $content, array $responses)
  {
    $now = new DateTime('now');
    $generated = $now->format('M j, Y');
    $this->footer_config['timestamp'] = "generated: $generated";

    $content_root = new SummaryRootBox($this,$content,$responses);
    $content_root->layoutChildren($this);
    $this->page_count = PDFBox::numPages();
    $content_root->render($this);
  }
}
