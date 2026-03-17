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
  private float $column_count = 0;
  private float $column_width = 0;
  private float $row_count    = 0;
  private float $row_height   = 0;

  private const hgap = K_QUARTER_INCH;
  private const vgap = 1; // mm

  /**
   * constructor
   * @param SummaryPDF $summaryPDF 
   * @param float $width 
   * @param array $responses 
   * @return void 
   */
  public function __construct(SummaryPDF $summaryPDF, float $width, array $responses)
  {
    parent::__construct($summaryPDF);
    $userids = array_keys($responses);
    sort_userids_by_fullname($userids);

    $max_name_width = 0;
    foreach($userids as $userid) {
      $user = User::from_userid($userid);
      $name = $user->fullname();

      $box = new PDFTextBox($summaryPDF, $width, $name, size:K_SUMMARY_FONT_MEDIUM);
      $this->names[] = $box;
      $max_name_width = max($max_name_width, $box->getWidth());
    }

    $this->column_width = $max_name_width + self::hgap;

    $ncol = floor($width / $this->column_width);
    $nrow = ceil(count($this->names)/$ncol);
    $this->column_count = $ncol;
    $this->row_count = $nrow;
    $this->row_height = $this->names[0]->getHeight();
    $this->height = $nrow*$this->row_height + ($nrow-1)*self::vgap;
  }

  /**
   * Manages the layout of a list response box
   * @param float $x 
   * @param float $y 
   * @return bool 
   */
  protected function position(float $x, float $y): bool 
  {
    parent::position($x,$y);

    $num_names = count($this->names);
    $row = 0;
    $col = 0;
    for($i=0; $i<$num_names; ++$i) {
      $ni = $row * $this->column_count + $col;
      $xi = $x + $col * $this->column_width;
      $yi = $y + $row * ($this->row_height + self::vgap);
      $this->names[$ni]->position($xi,$yi);

      $col += 1;
      if($col >= $this->column_count) {
        $col = 0;
        $row += 1;
      }
    }
    return true;
  }

  /**
   * Renders the content of a list response box
   * @return bool 
   */
  protected function render() : bool
  {
    if(!parent::render()) { return false; }
    foreach($this->names as $box) {
      if(!$box->render()) { return false; }
    }
    return true;
  }
  
  protected function debug_color(): array { return [255,128,0]; }
}