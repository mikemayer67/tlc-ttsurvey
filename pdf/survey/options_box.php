<?php
namespace tlc\tts;

use TCPDF;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));
require_once(app_file('pdf/survey/option_box.php'));
require_once(app_file('pdf/survey/other_box.php'));
require_once(app_file('pdf/survey/alignable_box.php'));
require_once(app_file('pdf/survey/enums.php'));

class SurveyOptionsBox extends PDFBox
{
  private bool         $_inline = false;
  private OptionLayout $_layout;

  /** @var SurveyAlignableBox[] */
  private array           $_children = [];
  /** @var PDFBox[][] */
  private array           $_rows = [];

  const vgap = K_EIGHTH_INCH/2;
  const hgap = K_EIGHTH_INCH;

  /**
   * Whether or not the options box should be on same line as the 
   *   question label
   * @return bool 
   */
  public function inline() : bool { return $this->_inline; }

  /**
   * Returns the height of the first row in the options box
   * @return float 
   */
  public function first_row_height() : float
  {
    $rval = 0;
    foreach ($this->_rows[0] as $box) {
      $rval = max($rval, $box->getHeight());
    }
    return $rval;
  }

  /**
   * @param TCPDF $tcpdf 
   * @param float $max_width 
   * @param float $inline_width 
   * @param array $question 
   * @param array $options 
   * @param int $fontsize (default=k_SURVEY_FORM_MEDIUM)
   * @return void 
   */
  public function __construct(
    TCPDF $tcpdf, float $max_width, float $inline_width,
    array $question, array $options, int $fontsize=K_SURVEY_FONT_MEDIUM)
  {
    $type = $question['type'];
    $shape = OptionShape::fromInput($type);
    $question_layout = $question['layout'] ?? 'ROW';
    $justification = SurveyJustification::fromInput($question_layout);
    $layout = OptionLayout::fromInput($question_layout);

    $this->_layout = $layout;
    
    $this->_children = [];
    foreach($question['options'] as $option) {
      $label = $options[$option];
      $this->_children[] = new SurveyOptionBox(
        $tcpdf, $max_width, $label, $shape, $justification, fontsize:$fontsize,
      );
    }
    if($question['other_flag']) {
      $label = $question['other'] ?? "Other";
      $this->_children[] = new SurveyOtherBox(
        $tcpdf, $max_width, $label, $shape, $justification, fontsize:$fontsize
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
    $this->_width = 0;
    $this->_height = 0;
    $aligned_width = 0;
    foreach($this->_children as $child) {
      $this->_width = max($this->_width, $child->getWidth());
      $this->_height += $child->getHeight() + self::vgap;
      $this->_rows[] = [$child];
      $aligned_width = max($aligned_width, $child->getAlignedWidth());
    }
    $this->_inline = ($this->_width <= $inline_width);
    foreach($this->_children as $child) {
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
    $this->_width = 0;
    $this->_height = 0;

    // determine total width if all options are on a single row
    $width = 0;
    $height = 0;
    foreach($this->_children as $child) {
      $width += $child->getWidth() + self::hgap;
      $height = max($height,$child->getHeight());
    }

    $this->_inline = ($width <= $inline_width);

    // if total width fits in the inline width, we're done
    if($this->_inline) 
    {
      $this->_width = $width;
      $this->_height = $height;
      $this->_rows = [$this->_children];
      return;
    } 

    // need to break the children into rows

    $row = [];
    $row_width = 0;
    $row_height = 0;
    foreach($this->_children as $child) 
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
        $this->_rows[] = $row;
        $this->_width = max($this->_width,$row_width);
        $this->_height += $row_height + self::vgap;
        $row = [$child];
        // now start a new row
        $row_width  = $box_width + self::hgap;
        $row_height = $box_height;
      }
    }

    // add the final row to the list of rows

    $this->_rows[] = $row;
    $this->_width = max($this->_width, $row_width);
    $this->_height += $row_height + self::vgap;
  }

  protected function position( float $x, float $y)
  {
    parent::position($x,$y);

    foreach($this->_rows as $row) 
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
  }

  public function render(): bool
  {
    foreach($this->_children as $box) {
      if(!$box->render()) { return false; }
    }
    return true;
  }
}