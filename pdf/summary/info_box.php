<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/summary/question_box.php'));
require_once(app_file('summary/markdown.php'));

class SummaryInfoBox extends SummaryQuestionBox
{
  private PDFTextBox $text_box;

  public const indent = K_QUARTER_INCH;
  /**
   * @param SummaryPDF $summaryPDF 
   * @param float $width 
   * @param array $question 
   * @param null|SummaryQuestionBox $prev 
   * @return void 
   */
  public function __construct(
    SummaryPDF $summaryPDF,
    float $width,
    array $question,
    ?SummaryQuestionBox $prev = null
  ) {
    parent::__construct($summaryPDF,$prev);

    // $qid       = $question['id'];
    // $wording   = $question['wording'];
    // $user_responses = $responses['questions'][$qid] ?? [];

    $this->width = $width;

    $info = strip_markdown($question['info']);
    $this->text_box = new PDFTextBox(
      $summaryPDF, $width, $info, style:'I',size:K_SUMMARY_FONT_LARGE, multi:true
    );

    $this->height = $this->text_box->getHeight();
  }

  /**
   * Always set the info text without indentation
   * @return bool 
   */
  public function resetIndent(): bool
  {
    return true;
  }

  /**
   * Indentation starts after an in-group info box
   * @return float 
   */
  public function incrementIndent(): float
  {
    return self::indent;
  }

  /**
   * Determines if there is enough room left on the page to fit
   *   all questions that follow within the group.  If not, returns true
   * @param float $y location where box will be positioned on current page
   * @param PDFRootBox $root container into which box will be positioned
   * @return bool 
   */
  protected function needsPageBreak(float $y, PDFRootBox $root): bool
  {
    $full_height = $this->height;   // height of info + all grouped items
    $widow_height = $this->height;  // height of info + first grouped item

    $prior = $this;
    $cur = $this->next;
    $widow_height += $cur->yOffset($prior) + $cur->getHeight();

    while($cur && !($cur instanceof SummaryInfoBox)) {
      $full_height += $cur->yOffset($prior) + $cur->getHeight();
      $prior = $cur;
      $cur = $cur->next;
    }
    if( $y + $full_height < $root->content_bottom() ) { 
      // info + all grouped items fits on the current page, no page break needed
      return false;
    }
    if( $full_height < $root->content_height() ) {
      // info + all grouped items would fit on an empty page, start a new page
      return true;
    }
    // not possible to fit info + all grouped items on a single page
    if( $y + $widow_height > $root->content_bottom() ) {
      // cannot even fit info + 1 item on the current page, start a new page
      return true;
    }
    // go ahead and put the info and whatever will fit on this page
    // the rest of the content will follow normal page break rules
    return false;
  }

  /**
   * Manages the layout of the info text box
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position($x, $y)
  {
    parent::position($x,$y);
    $this->text_box->position($x,$y);
  }

  /**
   * Renders the info text box
   * @return void
   */
  protected function render()
  {
    parent::render();
    $this->text_box->render();
  }

//  protected function debug_color(): array { return [255,0,255]; }
}

