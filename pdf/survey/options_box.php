<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/option_box.php'));
require_once(app_file('pdf/survey/other_box.php'));
require_once(app_file('pdf/survey/alignable_box.php'));
require_once(app_file('pdf/survey/enums.php'));

class SurveyOptionsBox extends PDFBox
{
  private bool         $inline = false;
  private OptionLayout $layout;

  /** @var SurveyAlignableBox[] */
  private array           $children = [];
  /** @var PDFBox[][] */
  private array           $rows = [];

  const vgap = K_EIGHTH_INCH/2;
  const hgap = K_EIGHTH_INCH;

  /**
   * Whether or not the options box should be on same line as the 
   *   question label
   * @return bool 
   */
  public function inline() : bool { return $this->inline; }

  /**
   * Returns the height of the first row in the options box
   * @return float 
   */
  public function first_row_height() : float
  {
    $rval = 0;
    foreach ($this->rows[0] as $box) {
      $rval = max($rval, $box->getHeight());
    }
    return $rval;
  }

  /**
   * @param SurveyPDF $surveyPDF 
   * @param float $max_width 
   * @param float $inline_width 
   * @param array $question 
   * @param array $options 
   * @param int $fontsize (default=k_SURVEY_FORM_MEDIUM)
   * @return void 
   */
  public function __construct(
    SurveyPDF $surveyPDF, float $max_width, float $inline_width,
    array $question, array $options, int $fontsize=K_SURVEY_FONT_MEDIUM)
  {
    parent::__construct($surveyPDF);
    
    $type = $question['type'];
    $shape = OptionShape::fromInput($type);
    $question_layout = $question['layout'] ?? 'ROW';
    $justification = SurveyJustification::fromInput($question_layout);
    $layout = OptionLayout::fromInput($question_layout);

    $this->layout = $layout;
    
    $this->children = [];
    foreach($question['options'] as $option) {
      $label = $options[$option];
      $this->children[] = new SurveyOptionBox(
        $surveyPDF, $max_width, $label, $shape, $justification, fontsize:$fontsize,
      );
    }
    if($question['other_flag']) {
      $label = $question['other'] ?? "Other";
      $this->children[] = new SurveyOtherBox(
        $surveyPDF, $max_width, $label, $shape, $justification, fontsize:$fontsize
      );
    }

    if($layout === OptionLayout::ROW) {
      $this->construct_rows($max_width,$inline_width);
    } else {
      $this->construct_column($max_width,$inline_width);
    }
  }

  /**
   * @param float $max_width 
   * @param float $inline_width 
   * @return void 
   */
  private function construct_column(float $max_width, float $inline_width)
  {
    $this->width = 0;
    $this->height = 0;
    $aligned_width = 0;
    foreach($this->children as $child) {
      $this->width = max($this->width, $child->getWidth());
      $this->height += $child->getHeight() + self::vgap;
      $this->rows[] = [$child];
      $aligned_width = max($aligned_width, $child->getAlignedWidth());
    }
    $this->inline = ($this->width <= $inline_width);
    foreach($this->children as $child) {
      $child->setAlignedWidth($aligned_width);
    }
  }

  /**
   * @param float $max_width 
   * @param float $inline_width 
   * @return void 
   */
  private function construct_rows(float $max_width, float $inline_width)
  {
    $this->width = 0;
    $this->height = 0;

    // determine total width if all options are on a single row
    $width = 0;
    $height = 0;
    foreach($this->children as $child) {
      $width += $child->getWidth() + self::hgap;
      $height = max($height,$child->getHeight());
    }

    $this->inline = ($width <= $inline_width);

    // if total width fits in the inline width, we're done
    if($this->inline) 
    {
      $this->width = $width;
      $this->height = $height;
      $this->rows = [$this->children];
      return;
    } 

    // need to break the children into rows

    $row = [];
    $row_width = 0;
    $row_height = 0;
    foreach($this->children as $child) 
    {
      $box_width  = $child->getWidth();
      $box_height = $child->getHeight();
      if($row_width + $box_width <= $max_width) {
        // box fits on the current row, add it to the row
        $row[] = $child;
        $row_width += $box_width + self::hgap;
        $row_height = max($row_height, $box_height);
      }
      else
      {
        // box doesn't fit, start a new row
        // but first, add the current row to the list of rows
        $this->rows[] = $row;
        $this->width = max($this->width,$row_width);
        $this->height += $row_height + self::vgap;
        $row = [$child];
        // now start a new row
        $row_width  = $box_width + self::hgap;
        $row_height = $box_height;
      }
    }

    // add the final row to the list of rows

    $this->rows[] = $row;
    $this->width = max($this->width, $row_width);
    $this->height += $row_height + self::vgap;
  }

  /**
   * Manages positioning of an options box and its children
   * @param float $x 
   * @param float $y 
   * @return bool 
   */
  protected function position(float $x, float $y) : bool
  {
    parent::position($x,$y);

    foreach($this->rows as $row) 
    {
      $bx = $x;
      $row_height = 0;
      foreach($row as $box) {
        $row_height = max($row_height, $box->getHeight());
      }
      foreach($row as $box) {
        $box_height = $box->getHeight();
        $dy = ($row_height - $box_height)/2;

        $box->position($bx,$y+$dy);
        $bx += $box->getWidth() + self::hgap;
      }
      $y += $row_height + self::vgap;
    }
    return true;
  }

  public function render(): bool
  {
    if (!parent::render()) { return false; }
    foreach($this->children as $box) {
      if(!$box->render()) { return false; }
    }
    return true;
  }

  protected function debug_color(): array
  {
    return [255,0,0];
  }
}