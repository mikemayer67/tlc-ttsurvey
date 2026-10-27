<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/summary/question_box.php'));
require_once(app_file('pdf/summary/option_box.php'));
require_once(app_file('pdf/summary/other_box.php'));
require_once(app_file('pdf/summary/qualifiers_box.php'));
require_once(app_file('include/question/types.php'));

class SummarySelectBox extends SummaryQuestionBox
{
  private PDFTextBox $label_box;
  private ?SummaryQualifiersBox $qualifiers_box = null;
  /** @var SummaryOptionBox[] $option_boxes */
  private array $option_boxes = [];

  
  /**
   * @param SummaryPDF $summaryPDF 
   * @param float $width 
   * @param SelectQuestion $question 
   * @param array $option_pool
   * @param array $responses 
   * @param null|SummaryQuestionBox $prev 
   */
  public function __construct(
    SummaryPDF $summaryPDF,
    float $width,
    SelectQuestion $question,
    array $option_pool,
    array $responses,
    ?SummaryQuestionBox $prev = null
  ) {
    parent::__construct($summaryPDF, $prev);
    $this->width = $width;

    $wording = $question->wording();
    $this->label_box = new PDFTextBox(
      $summaryPDF, $width, $wording,
      style: 'B', size: K_SUMMARY_FONT_LARGE
    );
    $this->height = $this->label_box->getHeight();
    $width -= self::indent;

    $option_ids = $question->options();
    $options = array_combine(
      $option_ids,
      array_map(fn($oid)=>$option_pool[$oid], $option_ids)
    );
    if($question->has_other()) { $options[0] = $question->other_prompt(); }

    $multi = $question->is_multi();
    foreach($options as $option_id=>$option) {
      $box = null;
      if($option_id && $multi) {
        $users = array_filter($responses, fn($a) => in_array($option_id, $a['options'] ?? []));
      } else {
        $users = array_filter($responses, fn($a) => $a['selected'] === $option_id);
      }

      if($option_id) { // not "other"
        $box = new SummaryOptionBox($summaryPDF, $width, $option, array_keys($users));
      }
      elseif($users) { // "other", but only if there are responses
        $users = array_map(fn($r)=>$r['other'],$users);
        $box = new SummaryOtherBox($summaryPDF,$width,$option,$users);
      }
      
      if($box) {
        $this->option_boxes[] = $box;
        $this->height += $box->getHeight();
      }
    }

    $qualifiers = array_filter($responses, fn($a) => ($a['qualifier']??''));
    if ($qualifiers) {
      $qualifiers = array_map(fn($r)=>$r['qualifier'],$qualifiers);
      $this->qualifiers_box = new SummaryQualifiersBox( $summaryPDF, $width, $question->qualifier(), $qualifiers);
      $this->height += self::vgap + $this->qualifiers_box->getHeight();
    }
  }

  /**
   * Manages the layout of a select box and its children
   * @param float $x 
   * @param float $y 
   */
  protected function position(float $x, float $y) : void
  {
    parent::position($x, $y);
    $this->label_box->position($x,$y);
    $y += $this->label_box->getHeight();

    $x += self::indent;

    foreach($this->option_boxes as $box) {
      $box->position($x,$y);
      $y += $box->getHeight();
    }

    if($this->qualifiers_box) {
      $y += self::vgap;
      $this->qualifiers_box->position($x, $y);
      $y += $this->qualifiers_box->getHeight();
    }
  }

  /**
   * Renders the content of a select box
   * @return void
   */
  protected function render()
  {
    parent::render();
    $this->label_box->render();
    foreach($this->option_boxes as $box) { $box->render(); }
    $this->qualifiers_box?->render();
  }

  protected function debug_color(): array { return [0,255,0]; }
}

