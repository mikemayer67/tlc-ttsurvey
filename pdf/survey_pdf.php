<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/imagelib.php'));
require_once(app_file('pdf/tcpdf_config.php'));
require_once(app_file('pdf/tcpdf_utils.php'));
require_once(app_file('pdf/survey/root_box.php'));
require_once(app_file('vendor/autoload.php'));

use TCPDF;
use DateTime;

class SurveyPDF extends TCPDF
{
  // Overload the TCPDF methods that we will want to customize.

  protected $page_count = 0;
  protected $title = null;
  protected $modified = null;

  public function __construct()
  {
    parent::__construct(
      'P',        // Portrait
      'mm',       // Units
      'LETTER',   // Page size (8.5" x 11")
      true,       // Unicode
      'UTF-8',
      false
    );

    $author = app_name() . " Admin";
    if ($userid = active_userid()) {
      if ($user = User::from_userid($userid)) {
        $author = $user->fullname();
      }
    }

    $creator = app_name();
    $repo = app_repo();
    if ($repo) {
      $creator .= " ($repo)";
    }

    $this->SetCreator($creator);
    $this->SetAuthor($author);
    $this->SetTitle($this->title);
    $this->SetSubject("Printable version of the online survey form");

    $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT, true);
    $this->SetHeaderMargin(PDF_MARGIN_HEADER);
    $this->SetFooterMargin(PDF_MARGIN_FOOTER);
    $this->SetAutoPageBreak(false);
  }

  public function Header(): void
  {
    $page = $this->getPage();

    $logo_height = 3 * K_EIGHTH_INCH;
    $logo_width  = 0;
    $logo_margin = 0;
    $logo_file = ImageLibrary::app_logo_file();
    if ($logo_file) {
      $logo_width = $logo_height;
      $logo_margin = 1; // mm
    }

    $this->SetFont(K_SERIF_FONT, size: 20);
    $title_height = tcpdf_line_height($this);
    $extra_height = $logo_height - $title_height;

    $this->setCellPaddings($logo_width + K_EIGHTH_INCH / 2, bottom: $extra_height / 2);
    $this->Cell(0, $logo_height + 2 * $logo_margin, $this->title, border: 'B', align: 'L');
    $this->Ln(5);

    if ($page === 1) {
      $this->setCellPaddings(0, 0, 0, 0);
      $this->SetFont(K_SANS_SERIF_FONT, 'I', 7);
      $this->SetY(PDF_MARGIN_HEADER + $logo_height + 2 * $logo_margin);
      $this->SetX(6.5 * K_INCH);
      $this->Cell(0, 0, '(participant name)');
    }

    if ($logo_file) {
      $this->Image($logo_file, PDF_MARGIN_LEFT, PDF_MARGIN_HEADER + $logo_margin, $logo_width, $logo_height);
    }
  }

  public function Footer(): void
  {
    $this->SetFont(K_SANS_SERIF_FONT, size: 8);
    $line_height = tcpdf_line_height($this);
    $this->SetY(-PDF_MARGIN_FOOTER - $line_height);

    $cell_width = ($this->getPageWidth() - ($this->lMargin + $this->rMargin)) / 2;

    $page = $this->getPage();
    $version = (new DateTime($this->modified))->format('Y.m.d');

    $this->SetFont(K_SANS_SERIF_FONT, size: 6);
    $this->Cell($cell_width, $line_height, "version: $version", 0, 0, 'L');
    $this->SetFont(K_SANS_SERIF_FONT, size: 8);
    $this->Cell($cell_width, $line_height, "Page $page of {$this->page_count}", 0, 0, 'R');
  }

    // Define the methods for precomputing all of the survey elements that will need 
    //   to be placed on the form.
    //
    // Footnote: this design is being driven by the fact that while we can know which
    //   page we're on when rendering each page, we also need to know the number of
    //   pages we will be rendering.  Normally PDF places a placeholder for the page
    //   information... but that makes right justification of the page number in the
    //   footer problematic.  This design allows us to know the page information while
    //   rendering each page and thus format the footer more cleanly.
    //   (yes, this is a silly detail... but easy enough to handle)

    // Finally... provide the methods for placing all of the elements onto the pages.

  /** Renders the PDF file given the survey content
   * @param $info array containing title, status, etc. about the survey being rendered
   * @param $content array (not even going to attempt to define it here)
   */
  public function render($info, $content)
  {
    $this->title = $info['title'];
    $this->modified = $info['modified'];

    $content_root = new SurveyRootBox($this, $content);
    $content_root->layoutChildren($this);
    $this->page_count = PDFBox::numPages();
    $content_root->render($this);
  }
}
