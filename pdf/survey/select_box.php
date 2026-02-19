<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/alignable_box.php'));
require_once(app_file('pdf/survey/options_box.php'));
require_once(app_file('pdf/survey/intro_box.php'));
require_once(app_file('pdf/survey/qualifier_box.php'));
require_once(app_file('pdf/survey/enums.php'));

class SurveySelectBox extends SurveyAlignableBox
{
  private PDFTextBox          $_wording;
  private SurveyOptionsBox    $_options;
  private ?SurveyIntroBox     $_intro_box = null;
  private ?SurveyQualifierBox $_qual_box = null;

  private float $_padding = 0;

  private static float $hgap = K_QUARTER_INCH;
  private static float $opt_indent = K_HALF_INCH;

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $max_width 
   * @param array $question 
   * @param array $options
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $max_width, array $question, array $options)
  {
    parent::__construct($tcpdf);

    $type    = $question['type'];
    $intro   = $question['intro'] ?? null;
    $wording = $question['wording'];
    $qual    = $question['qualifier'] ?? null;
    $layout  = $question['layout'] ?? "ROW";

    $justification = SurveyJustification::fromInput($layout);

    $shape  = OptionShape::fromInput($type);
    $layout = OptionLayout::fromInput($layout);

    if($intro) {
      $this->_intro_box = new SurveyIntroBox($tcpdf,$max_width,$intro);
      $max_width -= $this->_intro_box->incrementIndent();
      $this->_height += $this->_intro_box->getHeight();
      $this->_padding = 3;
    }

    $this->_wording = new PDFTextBox($tcpdf, $max_width, $wording);

    $this->_options = new SurveyOptionsBox(
      $tcpdf, $max_width - self::$opt_indent, 
      $max_width - ($this->_wording->getWidth() + self::$hgap),
      $question, $options,
    );
    $hw = $this->_wording->getHeight();
    $ho = $this->_options->getHeight();
    if($this->_options->inline()) {
      $this->_height += max($hw,$ho);
    } else {
      $this->_height += $hw + $ho;
    }


    if($qual) {
      $this->_qual_box = new SurveyQualifierBox($tcpdf,$max_width,$qual);
      $this->_height += $this->_qual_box->getHeight();
    }
  }

  /**
   * Manages positioning of a bool box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position( float $x, float $y)
  {
    parent::position($x, $y);
    $y += $this->_padding;

    // add (optional) intro box
    if($this->_intro_box) {
      $this->_intro_box->position($x,$y);
      $y += $this->_intro_box->getHeight();
      $x += $this->_intro_box->incrementIndent();
    }

    // add the guts of the select box
    $hw = $this->_wording->getHeight();
    $ho = $this->_options->getHeight();
    if($this->_options->inline()) {
      $xw = $x;
      $xo = $x + $this->_wording->getWidth() + self::$hgap;
      $hr = $this->_options->first_row_height();
      $dy = ($hr - $hw)/2;
      $yw = ($dy>0) ? $y + $dy : $y;
      $yo = ($dy<0) ? $y - $dy : $y;
      $this->_wording->position($xw, $yw);
      $this->_options->position($xo, $yo);
      $y += max($hw,$ho);
    } 
    else 
    {
      $this->_wording->position($x, $y);
      $this->_options->position($x + self::$opt_indent, $y + $hw);
      $y += $hw + $ho;
    }
      // add (optional) qual box
    if($this->_qual_box) {
      $this->_qual_box->position($x+K_QUARTER_INCH,$y);
    }

    $y += $this->_padding;
  }

  public function render(): bool
  {
    return (
      $this->_wording->render() &&
      $this->_options->render() &&
      ($this->_intro_box?->render() ?? true) &&
      ($this->_qual_box?->render() ?? true)
    );
  }
}
