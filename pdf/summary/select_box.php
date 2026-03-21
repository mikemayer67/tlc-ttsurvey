<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/summary/question_box.php'));
require_once(app_file('pdf/summary/option_box.php'));
require_once(app_file('pdf/summary/other_box.php'));
require_once(app_file('pdf/summary/qualifiers_box.php'));

class SummarySelectBox extends SummaryQuestionBox
{
  private PDFTextBox $label_box;
  private ?SummaryQualifiersBox $qualifiers_box = null;
  /** @var SummaryOptionBox[] $option_boxes */
  private array $option_boxes = [];
  /**
   * @param SummaryPDF $summaryPDF 
   * @param float $width 
   * @param array $question 
   * @param array $options
   * @param array $responses 
   * @param null|SummaryQuestionBox $prev 
   * @return void 
   */
  public function __construct(
    SummaryPDF $summaryPDF,
    float $width,
    array $question,
    array $options,
    array $responses,
    ?SummaryQuestionBox $prev = null
  ) {
    parent::__construct($summaryPDF, $prev);
    $this->width = $width;

    $wording = $question['wording'];
    $this->label_box = new PDFTextBox(
      $summaryPDF, $width, $wording,
      style: 'B', size: K_SUMMARY_FONT_LARGE
    );
    $this->height = $this->label_box->getHeight();

    $qid       = $question['id'];
    $responses = $responses['questions'][$qid] ?? [];

    if(!$responses) { return; } // no responses... we're done

    $options = array_combine(
      $question['options'],
      array_map(fn($oid)=>$options[$oid], $question['options'])
    );
    if($question['other_flag'] ?? false) {
      $options[0] = $question['other'] ?? 'Other';
    }

    $multi = strtolower($question['type']) === 'select_multi';
    foreach($options as $oid=>$option) {
      $box = null;
      if($oid && $multi) {
        $users = array_filter($responses, fn($a) => in_array($oid, $a['options'] ?? []));
      } else {
        $users = array_filter($responses, fn($a) => $a['selected'] === $oid);
      }

      if($oid) { // not "other"
        $box = new SummaryOptionBox($summaryPDF, $width, $option, array_keys($users));
      }
      elseif($users) { // "other", but only if there are responses
        $users = array_map(fn($r)=>$r['other'],$users);
        $box = new SummaryOtherBox($summaryPDF,$width,$option,$users);
      }
      
      if($box) {
        $this->option_boxes[] = $box;
        $this->height += self::vgap + $box->getHeight();
      }
    }

    $qualifiers = array_filter($responses, fn($a) => ($a['qualifier']??''));
    if ($qualifiers) {
      $qualifiers = array_map(fn($r)=>$r['qualifier'],$qualifiers);
      $this->qualifiers_box = new SummaryQualifiersBox(
        $summaryPDF, $width - self::indent,
        $question['qualifier'], $qualifiers
      );
      $this->height += self::vgap + $this->qualifiers_box->getHeight();
    }
  }

  /**
   * Manages the layout of a select box and its children
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position(float $x, float $y)
  {
    parent::position($x, $y);
    $this->label_box->position($x,$y);
    $y += $this->label_box->getHeight();

    $x += self::indent;

    foreach($this->option_boxes as $box) {
      $y += self::vgap;
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

