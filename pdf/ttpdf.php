<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/imagelib.php'));
require_once(app_file('pdf/ttpdf_config.php'));
require_once(app_file('pdf/ttpdf_utils.php'));
require_once(app_file('vendor/autoload.php'));

use \TCPDF;

/**
 * Provides common features between SurveyPDF and SummaryPDF
 * @package tlc\tts
 */
class TTPDF extends TCPDF
{
  // Overload the TCPDF methods that we will want to customize.
  protected $page_count = 0;
  protected $title = null;

  protected $header_config = [];
  protected $footer_config = [];

  /**
   * constructor
   * @param string $title
   * @param string $subject 
   * @return void 
   */
  public function __construct(string $title, string $subject)
  {
    parent::__construct(
      'P',        // Portrait
      'mm',       // Units
      'LETTER',   // Page size (8.5" x 11")
      true,       // Unicode
      'UTF-8',
      false
    );

    $this->title = $title;

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
    $this->SetTitle($title);
    $this->SetSubject($subject);
    $this->setViewerPreferences(['DisplayDocTitle'=>true]);

    $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT, true);
    $this->SetHeaderMargin(PDF_MARGIN_HEADER);
    $this->SetFooterMargin(PDF_MARGIN_FOOTER);
    $this->SetAutoPageBreak(false);
  }

  public function Header(): void
  {
    $page = $this->getPage();

    $logo_height = $this->header_config['logo_size'];
    $logo_width  = 0;
    $logo_margin = 0;
    $logo_file = ImageLibrary::app_logo_file();
    if ($logo_file) {
      $logo_width = $logo_height;
      $logo_margin = 1; // mm
    }

    $this->SetFont(K_SERIF_FONT, size: $this->header_config['title_fontsize']);
    $title_height = ttpdf_line_height($this);
    $extra_height = $logo_height - $title_height;

    $this->setCellPaddings($logo_width + K_EIGHTH_INCH / 2, bottom: $extra_height / 2);
    $this->Cell(0, $logo_height + 2 * $logo_margin, $this->title, border: 'B', align: 'L');
    $this->Ln(5);

    if ($page === 1 && $this->header_config['include_name_field']) {
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
    $line_height = ttpdf_line_height($this);
    $this->SetY(-PDF_MARGIN_FOOTER - $line_height);
    $fx = $this->GetX();

    $cell_width = ($this->getPageWidth() - ($this->lMargin + $this->rMargin)) / 3;

    $page = $this->getPage();

    $timestamp = $this->footer_config['timestamp'] ?? null;
    if($timestamp) {
      $this->SetFont(K_SANS_SERIF_FONT, size: 6);
      $this->Cell($cell_width, $line_height, $timestamp, 0, 0, 'L');
    }
    $section = $this->footer_config['section'] ?? null;
    if($section) {
      $this->SetX($fx + $cell_width);
      $this->SetFont(K_SANS_SERIF_FONT, size: 8);
      $this->Cell($cell_width, $line_height, $section, 0, 0, 'C');
    }
    $this->SetX($fx + 2 * $cell_width);
    $this->SetFont(K_SANS_SERIF_FONT, size: 8);
    $this->Cell($cell_width, $line_height, "Page $page of {$this->page_count}", 0, 0, 'R');
  }


}
