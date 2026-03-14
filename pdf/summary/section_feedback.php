<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/summary/config.php'));
require_once(app_file('include/users.php'));

class SummarySectionFeedback extends PDFBox
{ 
  private PDFTextBox $label_box;
  private PDFTextBox $feedback_box;
  private float      $responder_width;
  /** @var PDFTextBox[] $responder_boxes */
  private array      $responder_boxes = [];
  /** @var PDFTextBox[] $response_boxes */
  private array      $response_boxes = [];

  private const indent = K_QUARTER_INCH;
  private const vgap  = 2;
  private const vspace = 1; // mm
  /**
   * constructor
   * @param SummaryPDF $summaryPDF 
   * @param float $max_width 
   * @param array $section 
   * @param array $responses 
   * @return void 
   */
  public function __construct(SummaryPDF $summaryPDF, float $max_width, array $section, array $responses)
  {
    parent::__construct($summaryPDF);

    $name = $section['name'];
    $feedback = $section['feedback'] ?? null;
    
    $this->width = $max_width;

    $this->top_pad = K_EIGHTH_INCH;
    $this->label_box = new PDFTextBox(
      $summaryPDF, $max_width, "$name feedback: ",
      style:'B',size:K_SUMMARY_FONT_LARGE
    );
    $feedback_width = $max_width - $this->label_box->getWidth();
    
    $this->feedback_box =new PDFTextBox(
      $summaryPDF,$feedback_width,$feedback,K_SANS_SERIF_FONT,size:K_SUMMARY_FONT_LARGE
    );
    $this->height = max($this->feedback_box->getHeight(),$this->label_box->getHeight());

    $sid = $section['section_id'];
    $responses = $responses['sections'][$sid] ?? null;
    $this->responder_width = 0;
    if($responses) {
      $this->height += self::vgap;
      $name_heights = [];

      $userids = array_keys($responses);
      sort_userids_by_fullname($userids);
      foreach($userids as $userid) {
        $name = User::from_userid($userid)->fullname();
        $box = new PDFTextBox(
          $summaryPDF, $max_width/2, "$name: ", 
          style:'B', size:K_SUMMARY_FONT_MEDIUM
        );
        $this->responder_boxes[] = $box;
        $this->responder_width = max($this->responder_width, $box->getWidth());
        $name_heights[$userid] = $box->getHeight();
      }

      $max_width -= $this->responder_width + self::indent;
      foreach($userids as $userid) {
        $response = $responses[$userid];
        $box = new PDFTextBox(
          $summaryPDF, $max_width, $response, 
          style:'', size:K_SUMMARY_FONT_MEDIUM, multi:true
        );
        $this->response_boxes[] = $box;
        $this->height += self::vspace + max($name_heights[$userid],$box->getHeight());
      }
      $this->height -= self::vspace;
    }
  }

  /**
   * Manges the layout of the section feedback box
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position(float $x, float $y)
  {
    parent::position($x,$y);
    $hl = $this->label_box->getHeight();
    $hf = $this->feedback_box->getHeight();
    $dy = ($hl-$hf)/2;

    $this->label_box->position($x,$y+max(0,-$dy));
    $xo = $x + $this->label_box->getWidth();
    $this->feedback_box->position($xo,$y+max(0,$dy));
    $y += max($hl,$hf);

    if( $this->responder_boxes )
    {
      $xo = $x + $this->responder_width + self::indent;
      $y += self::vgap;
      foreach( $this->responder_boxes as $i=>$box1 ) {
        $box2 = $this->response_boxes[$i];

        $x1 = $xo - $box1->getWidth();
        $x2 = $xo;

        $dy = ($box2->getLineHeight() - $box1->getLineHeight())/2;
        $y1 = $y + max(0,$dy);
        $y2 = $y + max(0,-$dy);

        $box1->position($x1,$y1);
        $box2->position($x2,$y2);

        $y += max($box1->getHeight(), $box2->getHeight()) + self::vspace;
      }
    }
  }

    /**
   * Renders the content of a SummarySection box
   * @return bool 
   */
  protected function render(): bool
  {
    if (!parent::render()) { return false; }

    if (!$this->label_box->render()) { return false; }
    if (!$this->feedback_box->render())  { return false; }

    foreach($this->responder_boxes as $box) { if(!$box->render()) {return false;} }
    foreach($this->response_boxes  as $box) { if(!$box->render()) {return false;} }

    return true;
  }

  protected function debug_color(): array 
  { 
    return [128,0,0];
  }
}