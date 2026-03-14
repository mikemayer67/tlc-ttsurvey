<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/alignable_box.php'));
require_once(app_file('pdf/survey/options_box.php'));
require_once(app_file('pdf/survey/intro_box.php'));
require_once(app_file('pdf/survey/qualifier_box.php'));
require_once(app_file('pdf/survey/enums.php'));
require_once(app_file('pdf/survey/config.php'));

class SurveySelectBox extends SurveyAlignableBox
{
  private PDFTextBox          $wording;
  private SurveyOptionsBox    $options;
  private ?SurveyIntroBox     $intro_box = null;
  private ?SurveyQualifierBox $qual_box = null;

  private float $vpad = 0;

  private const hgap = K_QUARTER_INCH;
  private const opt_indent = K_HALF_INCH;

  /**
   * @param SurveyPDF $surveyPDF 
   * @param float $max_width 
   * @param array $question 
   * @param array $options
   * @return void 
   */
  public function __construct(SurveyPDF $surveyPDF, float $max_width, array $question, array $options)
  {
    parent::__construct($surveyPDF);

    $type    = $question['type'];
    $intro   = $question['intro'] ?? null;
    $wording = $question['wording'];
    $qual    = $question['qualifier'] ?? null;
    $layout  = $question['layout'] ?? "ROW";

    $justification = SurveyJustification::fromInput($layout);

    $shape  = OptionShape::fromInput($type);
    $layout = OptionLayout::fromInput($layout);

    if($intro) {
      $this->intro_box = new SurveyIntroBox($surveyPDF,$max_width,$intro);
      $max_width -= $this->intro_box->incrementIndent();
      $this->height += $this->intro_box->getHeight();
      $this->vpad = 3;
    }

    $this->wording = new PDFTextBox($surveyPDF, $max_width, $wording, size:K_SURVEY_FONT_MEDIUM);

    $this->options = new SurveyOptionsBox(
      $surveyPDF, $max_width - self::opt_indent, 
      $max_width - ($this->wording->getWidth() + self::hgap),
      $question, $options,
      fontsize:K_SURVEY_FONT_MEDIUM,
    );
    $hw = $this->wording->getHeight();
    $ho = $this->options->getHeight();
    if($this->options->inline()) {
      $this->height += max($hw,$ho);
    } else {
      $this->height += $hw + $ho;
    }
    $this->width = $max_width;

    if($qual) {
      $this->qual_box = new SurveyQualifierBox($surveyPDF,$max_width,$qual);
      $this->height += $this->qual_box->getHeight();
      $this->vpad = 3;
    }

    $this->height += 2*$this->vpad;
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
    $y += $this->vpad;

    // add (optional) intro box
    if($this->intro_box) {
      $this->intro_box->position($x,$y);
      $y += $this->intro_box->getHeight();
      $x += $this->intro_box->incrementIndent();
    }

    // add the guts of the select box
    $hw = $this->wording->getHeight();
    $ho = $this->options->getHeight();
    if($this->options->inline()) {
      $xw = $x;
      $xo = $x + $this->wording->getWidth() + self::hgap;
      $hr = $this->options->first_row_height();
      $dy = ($hr - $hw)/2;
      $yw = ($dy>0) ? $y + $dy : $y;
      $yo = ($dy<0) ? $y - $dy : $y;
      $this->wording->position($xw, $yw);
      $this->options->position($xo, $yo);
      $y += max($hw,$ho);
    } 
    else 
    {
      $this->wording->position($x, $y);
      $this->options->position($x + self::opt_indent, $y + $hw);
      $y += $hw + $ho;
    }
      // add (optional) qual box
    if($this->qual_box) {
      $this->qual_box->position($x+K_QUARTER_INCH,$y);
    }

    $y += $this->vpad;
  }

  public function render(): bool
  {
    if (!parent::render()) { return false; }
    return (
      $this->wording->render() &&
      $this->options->render() &&
      ($this->intro_box?->render() ?? true) &&
      ($this->qual_box?->render() ?? true)
    );
  }

  protected function debug_color(): array
  {
    return [0,255,0];
  }
}
