<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('include/users.php'));
require_once(app_file('pdf/pdf_boxes.php'));

/**
 * SummaryListResponse is used to render a list of responders
 *   The list contains the responder's full names arranged in
 *   columns.
 * @package tlc\tts
 */
class SummaryListResponseBox extends PDFBox
{
  /** @var PDFBox[] $names */
  private array $names = [];
  /** @var int[] $row_counts */
  private array $row_counts = [];
  private float $row_height = 0;

  private const hgap = K_QUARTER_INCH;
  private const vgap = 1; // mm

  /**
   * constructor
   * @param SummaryPDF $summaryPDF 
   * @param float $width 
   * @param string[] $userids 
   * @return void 
   */
  public function __construct(SummaryPDF $summaryPDF, float $width, array $userids)
  {
    parent::__construct($summaryPDF);

    sort_userids_by_fullname($userids);

    $this->height = 0;
    $row_width = 0;
    $row_count = 0;
    foreach($userids as $userid) {
      $user = User::from_userid($userid);
      $name = $user->fullname();

      $box = new PDFTextBox($summaryPDF, $width/4, $name, size:K_SUMMARY_FONT_MEDIUM);
      $this->names[] = $box;

      $this->row_height = max($this->row_height,$box->getHeight());

      $bw = $box->getWidth();
      if($row_width + $bw + self::hgap <= $width) {
        $row_count += 1;
        $row_width += $bw + self::hgap;
      } else {
        $this->row_counts[] = $row_count;
        $row_count = 1;
        $row_width = $bw;
      }
    }
    $this->row_counts[] = $row_count;

    $nrow = count($this->row_counts);
    $this->height = $nrow*$this->row_height + ($nrow-1)*self::vgap;
  }

  /**
   * Manages the layout of a list response box
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position(float $x, float $y)
  {
    parent::position($x,$y);

    $row = 0;
    $row_count = 0;
    $xi = $x;
    foreach($this->names as $box) {
      $box->position($xi,$y);
      $row_count += 1;
      if($row_count < $this->row_counts[$row]) {
        $xi += $box->getWidth() + self::hgap;
      } else {
        $row += 1;
        $row_count = 0;
        $xi = $x;
        $y += $this->row_height + self::vgap;
      }
    }
  }

  /**
   * Renders the content of a list response box
   * @return void 
   */
  protected function render()
  {
    parent::render();
    foreach($this->names as $box) { $box->render(); }
  }
  
  protected function debug_color(): array { return [0,255,255]; }
}