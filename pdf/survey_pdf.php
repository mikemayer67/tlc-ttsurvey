<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('include/login.php'));
require_once(app_file('include/users.php'));
require_once(app_file('pdf/tcpdf_config.php'));
require_once(app_file('pdf/tcpdf_utils.php'));
require_once(app_file('pdf/pdf_box.php'));
require_once(app_file('survey/markdown.php'));
require_once(app_file('vendor/autoload.php'));

use TCPDF;
use DateTime;

class SurveyPDF extends TCPDF
{
  // Overload the TCPDF methods that we will want to customize.

  protected $page_count = 0;
  protected $title = null;
  protected $modified = null;

  public function __construct()
  {
    parent::__construct(
      'P',        // Portrait
      'mm',       // Units
      'LETTER',   // Page size (8.5" x 11")
      true,       // Unicode
      'UTF-8',
      false
    );

    $author = app_name() . " Admin";
    if ($userid = active_userid()) {
      if ($user = User::from_userid($userid)) {
        $author = $user->fullname();
      }
    }

    $creator = app_name();
    $repo = app_repo();
    if ($repo) {
      $creator .= " ($repo)";
    }

    $this->SetCreator($creator);
    $this->SetAuthor($author);
    $this->SetTitle($this->title);
    $this->SetSubject("Printable version of the online survey form");

    $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT, true);
    $this->SetHeaderMargin(PDF_MARGIN_HEADER);
    $this->SetFooterMargin(PDF_MARGIN_FOOTER);
    $this->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
  }

  public function Header(): void
  {
    $page = $this->getPage();

    $icon_height = 3 * K_EIGHTH_INCH;
    $icon_width  = 0;
    $logo = app_logo();
    if ($logo) {
      $logo_file = app_file("img/$logo");
      $logo_size = getimagesize($logo_file);
      $logo_width = $logo_size[0];
      $logo_height = $logo_size[1];

      $icon_width = $icon_height * $logo_width / $logo_height;
      $icon_margin = 1; // mm
    }

    $this->SetFont(K_SERIF_FONT, size: 20);
    $title_height = tcpdf_line_height($this);
    $extra_height = $icon_height - $title_height;

    $this->setCellPaddings($icon_width + K_EIGHTH_INCH / 2, bottom: $extra_height / 2);
    $this->Cell(0, $icon_height + 2 * $icon_margin, $this->title, border: 'B', align: 'L');
    $this->Ln(5);

    if ($page === 1) {
      $this->setCellPaddings(0, 0, 0, 0);
      $this->SetFont(K_SANS_SERIF_FONT, 'I', 7);
      $this->SetY(PDF_MARGIN_HEADER + $icon_height + 2 * $icon_margin);
      $this->SetX(6.5 * K_INCH);
      $this->Cell(0, 0, '(participant name)');
    }

    if ($icon_width > 0) {
      $this->Image($logo_file, PDF_MARGIN_LEFT, PDF_MARGIN_HEADER + $icon_margin, $icon_width, $icon_height);
    }
  }

  public function Footer(): void
  {
    $this->SetFont(K_SANS_SERIF_FONT, size: 8);
    $line_height = tcpdf_line_height($this);
    $this->SetY(-PDF_MARGIN_FOOTER - $line_height);

    $cell_width = ($this->getPageWidth() - ($this->lMargin + $this->rMargin)) / 2;

    $page = $this->getPage();
    $version = (new DateTime($this->modified))->format('Y.m.d');

    $this->SetFont(K_SANS_SERIF_FONT, size: 6);
    $this->Cell($cell_width, $line_height, "version: $version", 0, 0, 'L');
    $this->SetFont(K_SANS_SERIF_FONT, size: 8);
    $this->Cell($cell_width, $line_height, "Page $page of {$this->page_count}", 0, 0, 'R');
  }

    // Define the methods for precomputing all of the survey elements that will need 
    //   to be placed on the form.
    //
    // Footnote: this design is being driven by the fact that while we can know which
    //   page we're on when rendering each page, we also need to know the number of
    //   pages we will be rendering.  Normally PDF places a placeholder for the page
    //   information... but that makes right justification of the page number in the
    //   footer problematic.  This design allows us to know the page information while
    //   rendering each page and thus format the footer more cleanly.
    //   (yes, this is a silly detail... but easy enough to handle)

    // Finally... provide the methods for placing all of the elements onto the pages.

  /** Renders the PDF file given the survey content
   * @param $info array containing title, status, etc. about the survey being rendered
   * @param $content array (not even going to attempt to define it here)
   */
  public function render($info, $content)
  {
    $this->title = $info['title'];
    $this->modified = $info['modified'];

    $content_root = new SurveyRootBox($this, $content);
    $content_root->layoutChildren($this);
    $this->page_count = $content_root->numPages();
    $content_root->render($this);
  }
}


/**
 * Responsible for parsing the survey content into top PDFBoxes
 * - Section boxes: adds a new section (which starts a new page)
 * - Question boxes: adds a single question
 * - Group boxes: adds a box containing multiple questions
 */
class SurveyRootBox extends PDFRootBox
{
  /**
   * Constructs all of the top level child boxes for the survey form
   * @param SurveyPDF $tcpdf 
   * @param array $content survey content structure data
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, array $content)
  {
    parent::__construct($tcpdf);

    $max_width = $this->content_width();

    // Sort the sections by sequence
    $sections = $content['sections'];
    usort($sections, fn($a, $b) => $a['sequence'] <=> $b['sequence']);
    foreach ($sections as $section) {
      $this->add_section($max_width, $section, $content);
    }
  }

  /**
   * Adds section content to the survey form
   * @param float $width 
   * @param array $section section specific content data
   * @param array $content overall survey content structure data
   * @return void 
   */
  private function add_section(float $width, array $section, array $content)
  {
    $box = new SurveySectionBox($this->_tcpdf, $width, $section);
    $this->addChild($box);

    $width -= $box->incrementIndent();

    $this->add_questions($width, $section['section_id'], $content);
  }

  /**
   * Adds all question content for the specified section id
   * @param float $width
   * @param int $sid section ID
   * @param array $content overall survey content structure data
   * @return void 
   */
  private function add_questions(float $width, int $sid, array $content): void
  {
    // find the questions to add to this section
    $questions = array_values(array_filter(
      $content['questions'],
      fn($q) => (
        (($q['section'] ?? null) === $sid) &&        // question must be associated with this section
        array_key_exists('sequence', $q)  // question must have a sequence index
      )
    ));

    // and sort them by sequence index
    usort($questions, function ($a, $b) {
      return $a['sequence'] <=> $b['sequence'];
    });

    // group the questions into groups that should not break between pages.
    $groups = [];
    $group = [];
    foreach($questions as $question) {
      switch( $question['grouped'] ?? 'NO' ) {
        case 'YES':
          $group[] = $question;
        break;
        case 'NEW':
          if ($group) { $groups[] = $group; }
          $group = [$question];
          break;
        default:
          if ($group) { $groups[] = $group; }
          $groups[] = [$question];
          $group = [];
          break;
      }
    }
    if($group) { $groups[] = $group; }

    // add question boxes to the survey
    foreach($groups as $questions) {
      $box = new SurveyGroupBox($this->_tcpdf, $width, $questions, $content);
      $this->addChild($box);
    }
  }
}

/**
 * Responsible for rendering a section header box
 */
class SurveySectionBox extends PDFBox
{
  private ?PDFBox $_name_box = null;
  private ?PDFBox $_intro_box = null;

  private float $_gap = 1; // mm

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $box_width
   * @param array $section 
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $width, array $section)
  {
    parent::__construct($tcpdf);

    $name        = $section['name'];
    $collapsible = $section['collapsible'] ?? true;
    $intro       = $section['intro'] ?? '';

    $this->_width = $width;
    $this->_height = 0;

    if ($collapsible || $intro) {
      $this->_top_pad    = 0.25 * K_INCH;
      $this->_bottom_pad = 0.125 * K_INCH;
    } else {
      $this->_top_pad = 0;
      $this->_bottom_pad = 0;
    }

    if ($collapsible) {
      $this->_name_box = new PDFTextBox($tcpdf, $width, $name, K_SERIF_FONT, size: 16);
      $this->_height += $this->_name_box->getHeight();
    }
    if ($collapsible && $intro) {
      $this->_height += $this->_gap;
    }
    if ($intro) {
      if (possibleMarkdown($intro)) {
        $this->_intro_box = new PDFMarkdownBox($tcpdf, $width, $intro, size: 9);
      } else {
        $this->_intro_box = new PDFTextBox($tcpdf, $width, $intro, style: 'I', size: 9, multi: true);
      }
      $this->_height += $this->_intro_box->getHeight();
    }
  }

  /**
   * Overrides the maxPagePos method.
   *   Section boxes should start in the two 2/3 of the page.
   * @return float 
   */
  public function maxPagePos(): float
  {
    return 0.67;
  }

  /**
   * Section boxes always reset the indent to 0
   * @return bool 
   */
  public function resetIndent(): bool
  {
    return true;
  }

  /**
   * Section boxes increase the indent for subsequent boxes if it contains an intro box
   * @return float amount by which to incrment the indent
   */
  public function incrementIndent(): float
  {
    return $this->_intro_box ? K_QUARTER_INCH : 0;
  }

  /**
   * Manages the layout of the section box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function layout(int $page, float $x, float $y)
  {
    parent::layout($page,$x,$y);

    if ($this->_name_box) {
      $this->_name_box->layout($page, $x, $y);
      $y += $this->_name_box->getHeight();
      if ($this->_intro_box) { $y += $this->_gap; }
    }
    if ($this->_intro_box) {
      $this->_intro_box->layout($page, $x, $y);
    }
  }

  /**
   * Renders the content of a SurveySection box
   * @return bool 
   */
  protected function render(): bool
  {
    if ($this->_name_box) {
      if (!$this->_name_box->render()) { return false; }
      $y = $this->_name_box->_y + $this->_name_box->getHeight();
      $this->_tcpdf->setLineWidth(0.2);
      $x1 = PDF_MARGIN_LEFT;
      $x2 = $this->_tcpdf->getPageWidth() - PDF_MARGIN_RIGHT;
      $this->_tcpdf->Line($x1, $y, $x2, $y);
    }

    if ($this->_intro_box) {
      if (!$this->_intro_box->render()) { return false; }
    }

    return true;
  }
}

class SurveyGroupBox extends PDFBox
{
  /**
   * @var PDFBox[] child question boxes
   */
  private array $_child_boxes = [];

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $max_width
   * @param array $questions 
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $max_width, array $questions, array $content)
  {
    parent::__construct($tcpdf);

    $this->_width = 0;
    $this->_height = 0;

    $this->_top_pad    = 2; // mm
    $this->_bottom_pad = 1; // mm

    $aligned_width = 0;
    foreach($questions as $question) {
      $type = $question['type'];

      switch($type) {
        case 'INFO':
          $box = new SurveyInfoBox($tcpdf,$max_width,$question);
          break;
        case 'FREETEXT':
          $box = new SurveyFreetextBox($tcpdf,$max_width,$question);
          break;
        case 'BOOL':
          $box = new SurveyBoolBox($tcpdf,$max_width,$question);
          break;
        default:
          $box = new PDFTextBox($tcpdf, $max_width, $type);
          break;
      }
      $this->_height += $box->getHeight();
      $this->_width = max($this->_width, $box->getWidth());
      $this->_child_boxes[] = $box;

      if($box instanceof SurveyQuestionBox) {
        $aligned_width = max($aligned_width, $box->alignedWidth());
      }

      $max_width -= $box->incrementIndent();
    }
    foreach($this->_child_boxes as $box) {
      if($box instanceof SurveyQuestionBox) {
        $box->alignedWidth($aligned_width);
      }
    }
  }

  /**
   * Manages the layout of a group box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function layout(int $page, float $x, float $y)
  {
    parent::layout($page, $x, $y);

    foreach($this->_child_boxes as $box) 
    {
      $box->layout($page,$x,$y);
      $y += $box->getHeight();
      $x += $box->incrementIndent();
    }
  }

  /**
   * Renders the content of a SurveyGroupBox
   * @return bool 
   */
  protected function render() : bool
  {
    foreach($this->_child_boxes as $box) 
    {
      if(!$box->render()) { return false; }
    }

    return true;
  }
}

abstract class SurveyQuestionBox extends PDFBox 
{
  protected float $_aligned_width = 0;
  protected string $_justification = 'left';

  /**
   * Getter/Setter for alignment width
   *   i.e. the part of the box which must be aligned
   * @param null|float $new_width 
   * @return float (getter: the cur value, setter: the old value)
   */
  public function alignedWidth(?float $new_width=null) : float
  {
    $rval = $this->_aligned_width;
    if($new_width !== null ) { $this->_aligned_width = $new_width; }
    return $rval;
  }

  /**
   * Getter/Setter for justification
   *   i.e. justification in the box which must be aligned
   *  Passing an invalid value (i.e. not 'left' or 'right') 
   * @param null|string $new_value 
   * @return null|string current value unless bad value passed to setter
   */
  public function justification(?string $new_value=null) : ?string
  {
    $rval = $this->_justification;
    if( $new_value !== null ) {
      $new_value = strtoupper($new_value);
      if (in_array($new_value, ['LEFT', 'RIGHT'])) {
        $this->_justification = $new_value;
      } else {
        $rval = null;
      }
    }
    return $rval;
  }
}

class SurveyIntroBox extends PDFBox
{
  private PDFBox $_box;

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $max_width 
   * @param string $intro 
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $max_width, string $intro)
  {
    parent::__construct($tcpdf);

    if (possibleMarkdown($intro)) {
      $this->_box = new PDFMarkdownBox($tcpdf, $max_width, $intro);
    } else {
      $this->_box = new PDFTextBox($tcpdf, $max_width, $intro, multi: true);
    }
    $this->_height = $this->_box->getHeight();
  }

  public function incrementIndent(): float { return K_QUARTER_INCH; }

  /**
   * Manages layout of a intro box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function layout(int $page, float $x, float $y)
  {
    parent::layout($page, $x, $y);
    $this->_box->layout($page, $x,$y);
  }

  /**
   * @return bool 
   */
  public function render(): bool
  {
    return $this->_box->render();
  }

}

class SurveyQualifierBox extends PDFBox
{
  private PDFBox $_label;
  private int    $_label_width = 0;
  private int    $_label_height = 0;
  private bool   $_multi_line = false;

  private array $_entry_box = [0,0,0,K_QUARTER_INCH];
  private float $_gap = 1; // mm

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $max_width 
   * @param string $label 
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $max_width, string $label)
  {
    parent::__construct($tcpdf);

    $this->_entry_box[2] = min(3*K_INCH, $max_width/2);

    $this->_label = new PDFTextBox($tcpdf, $max_width, $label);
    $this->_label_width  = $this->_label->getWidth();
    $this->_label_height = $this->_label->getHeight();
    if($this->_label_width + $this->_entry_box[2] + $this->_gap < $max_width) {
      $this->_multi_line = false;
      $this->_height += max($this->_label_height,$this->_entry_box[3]);
    } else {
      $this->_multi_line = true;
      $this->_height += $this->_label_height + $this->_gap + $this->_entry_box[3];
    }
  }

  /**
   * Manages layout of a qualifer box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function layout(int $page, float $x, float $y)
  {
    parent::layout($page, $x, $y);

    if($this->_multi_line) {
      $this->_label->layout($page, $x, $y);
      // set the (x,y) for the entry box on the next line
      $y += $this->_label_height + $this->_gap;
      $x += K_INCH;
    } else {
      $dy = ($this->_entry_box[3] - $this->_label_height)/2;
      if($dy >= 0) {
        // shift the label down so as to center on entry
        $this->_label->layout($page, $x, $y+$dy);
      } else {
        $this->_label->layout($page, $x, $y);
        // shift the entry down so as to center on label
        $y += $dy;
      }
      // shift the entry horizontally to after the label
      $x += $this->_label_width + $this->_gap;
    }
    $this->_entry_box[0] = $x;
    $this->_entry_box[1] = $y;
  }

  public function render(): bool
  {
    if(!$this->_label->render()) { return false; }
    $this->_tcpdf->Rect(...$this->_entry_box);
    return true;
  }
}

class SurveyInfoBox extends SurveyQuestionBox
{
  private PDFBox $_box;
  private bool $_new_group = false;
  /**
   * @param SurveyPDF $tcpdf 
   * @param float $max_width
   * @param array $question 
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $max_width, array $question)
  {
    parent::__construct($tcpdf);

    $info = $question['info'];

    $this->_new_group = strtoupper($question['grouped']??"") === "NEW";

    $this->_width = $max_width;
    $this->_height = 0;

    if(possibleMarkdown($info)) {
      $this->_box = new PDFMarkdownBox($tcpdf,$max_width,$info);
    } else {
      $this->_box = new PDFTextBox($tcpdf, $max_width, $info, multi:true);
    }
    $this->_height += $this->_box->getHeight();
  }

  /**
   * Info boxes increate the indent for subsequent boxes if they are
   * starting a new question group
   * @return float 
   */
  public function incrementIndent() : float
  {
    return $this->_new_group ? K_QUARTER_INCH : 0;
  }

  /**
   * Manages the layout of a info box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function layout(int $page, float $x, float $y)
  {
    parent::layout($page, $x, $y);
    $this->_box->layout($page, $x, $y);
  }

  /**
   * Renders the content of a SurveyInfo box
   * @return bool 
   */
  protected function render(): bool
  {
    return $this->_box->render();
  }
}

class SurveyFreetextBox extends SurveyQuestionBox
{
  private ?SurveyIntroBox $_intro_box = null;
  private PDFBox          $_wording_box;

  private array $_entry_box = [0,0,0,3*K_QUARTER_INCH];
  private float $_gap = 1; // mm

  /**
   * @param SurveyPDF $tcpdf 
   * @param float $max_width 
   * @param array $question 
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $max_width, array $question)
  {
    parent::__construct($tcpdf);

    $wording = $question['wording'];
    $intro   = $question['intro'] ?? null;

    $this->_width = $max_width;
    $this->_height = 0;

    if($intro) {
      $this->_intro_box = new SurveyIntroBox($tcpdf,$max_width,$intro);
      $max_width -= $this->_intro_box->incrementIndent();
      $this->_height += $this->_intro_box->getHeight();
    }

    $this->_wording_box = new PDFTextBox($tcpdf,$max_width,$wording);
    $this->_height += $this->_wording_box->getHeight();

    $this->_entry_box[2] = $max_width;
    $this->_height += $this->_entry_box[3];
  }

  /**
   * Manages layout of a free text box and its children
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function layout(int $page, float $x, float $y)
  {
    parent::layout($page, $x, $y);

    if($this->_intro_box) {
      $this->_intro_box->layout($page, $x, $y);
      $y += $this->_intro_box->getHeight() + $this->_gap;
      $x += $this->_intro_box->incrementIndent();
    }

    $this->_wording_box->layout($page, $x, $y);
    $y += $this->_wording_box->getHeight();

    $this->_entry_box[0] = $x;
    $this->_entry_box[1] = $y;
  }

  /**
   * Renders the content of a free text box
   * @return bool 
   */
  protected function render() : bool
  {
    $box = $this->_intro_box;
    if($box) {
      if(!$box->render()) { return false; }
    }
    $box = $this->_wording_box;
    if(!$box->render()) { return false; }
    $this->_tcpdf->setLineWidth(0.2);
    $this->_tcpdf->Rect(...$this->_entry_box);

    return true;
  }
}

class SurveyBoolBox extends SurveyQuestionBox
{
  private PDFBox  $_wording_box;
  private float   $_wording_height = 0;
  private float   $_wording_width  = 0;
  private ?SurveyIntroBox     $_intro_box = null;
  private ?SurveyQualifierBox $_qual_box = null;

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
   * @return void 
   */
  public function __construct(SurveyPDF $tcpdf, float $max_width, array $question)
  {
    parent::__construct($tcpdf);

    $intro   = $question['intro'] ?? null;
    $wording = $question['wording'];
    $qual    = $question['qualifier'] ?? null;

    if($intro) {
      $this->_intro_box = new SurveyIntroBox($tcpdf,$max_width,$intro);
      $max_width -= $this->_intro_box->incrementIndent();
      $this->_height += $this->_intro_box->getHeight();
    }

    $box = new PDFTextBox($tcpdf,$max_width,$wording);
    $this->_wording_box = $box;
    $this->_wording_width = $box->getWidth();
    $this->_wording_height = $box->getHeight();
    $this->_height += max($this->_wording_height, $this->_checkbox[3]);

    $this->alignedWidth($box->getWidth() + $this->_checkbox[2] + $this->_gap);
    $this->justification($question['layout'] ?? 'LEFT');

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

    // add (optional) intro box
    if($this->_intro_box) {
      $this->_intro_box->layout($page,$x,$y);
      $y += $this->_intro_box->getHeight();
      $x += $this->_intro_box->incrementIndent();
    }

    // add wording box + checkbox
    if($this->justification() === 'LEFT') {
      $xc = $x;
      $xw = $xc + $this->_checkbox[2] + $this->_gap;
    } else {
      $xc = $x + $this->alignedWidth() - $this->_checkbox[2];
      $xw = $xc - ( $this->_gap + $this->_wording_width );
    }
    $dy = ($this->_checkbox[3] - $this->_wording_height)/2;
    $yw = ($dy > 0) ? $y+$dy : $y;
    $yc = ($dy > 0) ? $y     : $y - $dy;

    $this->_wording_box->layout($page, $xw, $yw);
    $this->_checkbox[0] = $xc;
    $this->_checkbox[1] = $yc;

    $y += $this->_wording_box->getHeight();

    // add (optional) qual box
    if($this->_qual_box) {
      $this->_qual_box->layout($page,$x+K_QUARTER_INCH,$y);
    }
  }

  public function render(): bool
  {
    if(
      ($this->_intro_box?->render() ?? true) &&
      $this->_wording_box->render() &&
      ($this->_qual_box?->render() ?? true )
    ) {
      $this->_tcpdf->setLineWidth(0.2);
      $this->_tcpdf->RoundedRect(...$this->_checkbox);
      return true;
    }
    return false;
  }
}