<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('include/users.php'));
require_once(app_file('pdf/pdf_boxes.php'));

/**
 * SummaryTableResponseBox is used to render tabulated text responses
 *   column 1: full name
 *   column 2: response
 * @package tlc\tts
 */
class SummaryTableResponseBox extends PDFBox
{
  protected float $name_width = 0;
  protected float $response_width = 0;
  /** @var PDFTextBox[] $name_boxes */
  protected array $name_boxes = [];
  /** @var PDFTextBox[] $response_boxes */
  protected array $response_boxes = [];

  private float $vspace = 1; // mm

  /**
   * constructor
   * @param SummaryPDF $summaryPDF 
   * @param float $width 
   * @param array $responses 
   * @param float $fontsize
   * @param float $vspace betwen responses
   * @param bool $compact attempt to minimize width of second column 
   * @return void 
   */
  public function __construct(
    SummaryPDF $summaryPDF, 
    float $width, 
    array $responses,
    float $fontsize=K_SUMMARY_FONT_MEDIUM,
    float $vspace=1,
    bool $compact=false)
  {
    parent::__construct($summaryPDF);
    $this->vspace = $vspace;

    $this->name_width = 0;
    $this->response_width = 0;
    $name_heights = [];

    $userids = array_keys($responses);
    sort_userids_by_fullname($userids);
    foreach($userids as $userid) {
      $name = User::from_userid($userid)->fullname();
      $box = new PDFTextBox($summaryPDF,$width/2,"$name: ",size:$fontsize);
      $this->name_boxes[] = $box;
      $this->name_width = max($this->name_width, $box->getWidth());
      $name_heights[$userid] = $box->getHeight();
    }

    $this->response_width = $width - $this->name_width;

    if($compact) {
      $summaryPDF->SetFont(K_SANS_SERIF_FONT, '', $fontsize);
      $wb = $this->response_width;
      $wa = 1.5*K_INCH;
      $nb = $this->calc_total_lines($wb, $responses);
      $na = $this->calc_total_lines($wa, $responses);
      if($na <= $nb) {
        $this->response_width = $wa;
      } else {
        while($wb > $wa + K_QUARTER_INCH) {
          $wt = ($wa+$wb)/2;
          $nt = $this->calc_total_lines($wt, $responses);
          if($nt > $nb) { $wa = $wt; } 
          else          { $wb = $wt; }
        }
        $this->response_width = $wb;
      }
    }

    foreach($userids as $userid) {
      $response = $responses[$userid];
      $response = str_replace("\n","  ",$response);
      $box = new PDFTextBox( $summaryPDF,$this->response_width,$response,size:$fontsize,multi:true);
      $this->response_boxes[] = $box;
      $this->height += $this->vspace + max($name_heights[$userid],$box->getHeight());
    }
    $this->height -= $this->vspace; // remove the one extra vspace from the for loop above
    $this->width   = $this->name_width + $this->response_width;
  }

  /**
   * computes the total number of MultiCell lines necessary to render the responses at the specified width
   * @param float $width 
   * @param array $responses 
   * @return int 
   */
  private function calc_total_lines(float $width,array $responses) : int
  {
    $lines = 0;
    foreach($responses as $response) {
      $lines += $this->ttpdf->getNumLines($response,$width);
    }
    return $lines;
  }
  
  /**
   * Manages the layout of a response table box
   * @param float $x 
   * @param float $y 
   * @return bool 
   */
  protected function position(float $x, float $y) : bool
  {
    parent::position($x,$y);
    
    $xo = $x + $this->name_width;
    foreach( $this->name_boxes as $i=>$name_box ) {
      $response_box = $this->response_boxes[$i];

      $x1 = $xo - $name_box->getWidth();
      $x2 = $xo;

      $dy = ($response_box->getLineHeight() - $name_box->getLineHeight())/2;
      $y1 = $y + max(0,$dy);
      $y2 = $y + max(0,-$dy);

      $name_box->position($x1,$y1);
      $response_box->position($x2,$y2);

      $y += max($name_box->getHeight(), $response_box->getHeight()) + $this->vspace;
    }
    return true;
  }

  /**
   * Renders the content of a summary response table box
   * @return bool 
   */
  protected function render() : bool
  {
    if(!parent::render()) { return false; }
    foreach($this->name_boxes     as $box) { if(!$box->render()) { return false; } }
    foreach($this->response_boxes as $box) { if(!$box->render()) { return false; } }
    return true;
  }

  protected function debug_color(): array { return [255,128,0]; }
}