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

  private array $_checkbox = [
    0,0, // x,y
    K_EIGHTH_INCH,K_EIGHTH_INCH, // width,height
    K_INCH/32, // corner radius
    ];

  private float $_gap = 1; // mm

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
    }

    $this->_wording = new PDFTextBox($tcpdf, $max_width, $wording);

    $this->_options = new SurveyOptionsBox(
      $tcpdf, $max_width, 
      $max_width - ($this->_wording->getWidth() + K_QUARTER_INCH),
      $question, $options,
    );

    if($qual) {
      $this->_qual_box = new SurveyQualifierBox($tcpdf,$max_width,$qual);
      $this->_height += $this->_qual_box->getHeight();
    }
  }

  /**
   * Manages layout of a bool box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function layout(int $page, float $x, float $y)
  {
    parent::layout($page, $x, $y);


  }

  public function render(): bool
  {
    return true;
  }
}
