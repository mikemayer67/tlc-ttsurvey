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
    $content_root->computeLayout($this);
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
      $this->add_section($tcpdf, $max_width, $section, $content);
    }
  }

  /**
   * Adds section content to the survey form
   * @param SurveyPDF $tcpdf 
   * @param float $width 
   * @param array $section section specific content data
   * @param array $content overall survey content structure data
   * @return void 
   */
  private function add_section(SurveyPDF $tcpdf, float $width, array $section, array $content)
  {
    $box = new SurveySectionBox($tcpdf, $width, $section);
    $this->addChild($box);

    $width -= $box->incrementIndent();

    $this->add_questions($tcpdf, $width, $section['section_id'], $content);
  }

  /**
   * Adds all question content for the specified section id
   * @param SurveyPDF $tcpdf 
   * @param int $sid section ID
   * @param array $content overall survey content structure data
   * @return void 
   */
  private function add_questions(SurveyPDF $tcpdf, $width, int $sid, array $content): void
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

    // group the questions into boxes that should not break between pages.
    $question_boxes = [];
    $cur_box = [];
    foreach($questions as $question) {
      switch( $question['grouped'] ?? 'NO' ) {
        case 'YES':
          $cur_box[] = $question;
        break;
        case 'NEW':
          if ($cur_box) { $question_boxes[] = $cur_box; }
          $cur_box = [$question];
          break;
        default:
          if ($cur_box) { $question_boxes[] = $cur_box; }
          $question_boxes[] = [$question];
          $cur_box = [];
          break;
      }
    }
    if($cur_box) { $question_boxes[] = $cur_box; }

    // add question boxes to the survey
    foreach($question_boxes as $questions) {
      $box = new SurveyQuestionBox($tcpdf, $width, $questions, $content);
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
   * Computes the layout of the name/intro boxes within the section box
   * @param TCPDF $tcpdf 
   * @return void 
   */
  public function computeLayout(TCPDF $tcpdf)
  {
    $x = $this->_x;
    $y = $this->_y;

    if ($this->_name_box) {
      $this->_name_box->setPosition($this->_page, $x, $y);
      $y += $this->_name_box->getHeight();
      if ($this->_intro_box) { $y += $this->_gap; }
    }
    if ($this->_intro_box) {
      $this->_intro_box->setPosition($this->_page, $x, $y);
    }
  }

  /**
   * Renders the content of a SurveySection box
   * @param TCPDF $tcpdf 
   * @return bool 
   */
  protected function render(TCPDF $tcpdf): bool
  {
    if ($this->_name_box) {
      if (!$this->_name_box->render($tcpdf)) { return false; }
      $y = $this->_name_box->_y + $this->_name_box->getHeight();
      $tcpdf->setLineWidth(0.2);
      $x1 = PDF_MARGIN_LEFT;
      $x2 = $tcpdf->getPageWidth() - PDF_MARGIN_RIGHT;
      $tcpdf->Line($x1, $y, $x2, $y);
    }

    if ($this->_intro_box) {
      if (!$this->_intro_box->render($tcpdf)) { return false; }
    }

    return true;
  }
}

class SurveyQuestionBox extends PDFBox
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
    $this->_width = 0;
    $this->_height = 0;

    $this->_top_pad    = 2; // mm
    $this->_bottom_pad = 1; // mm

    foreach($questions as $question) {
      $type = $question['type'];

      switch($type) {
        case 'INFO':
          $box = new SurveyInfoBox($tcpdf,$max_width,$question);
          break;
        case 'FREETEXT':
          $box = new SurveyFreetextBox($tcpdf,$max_width,$question);
          break;
        default:
          $box = new PDFTextBox($tcpdf, $max_width, $type);
          break;
      }
      $this->_height += $box->getHeight();
      $this->_width = max($this->_width, $box->getWidth());
      $this->_child_boxes[] = $box;

      $max_width -= $box->incrementIndent();
    }
  }

  /**
   * Computes the layout of the question boxes
   * @param TCPDF $tcpdf 
   * @return void 
   */
  public function computeLayout(TCPDF $tcpdf)
  {
    $x = $this->_x;
    $y = $this->_y;

    foreach($this->_child_boxes as $box) 
    {
      $box->setPosition($this->_page,$x,$y);
      $y += $box->getHeight();
      $x += $box->incrementIndent();
      $box->computeLayout($tcpdf);
    }
  }

  /**
   * Renders the content of a SurveyQuestionBox
   * @param TCPDF $tcpdf 
   * @return bool 
   */
  protected function render(TCPDF $tcpdf) : bool
  {
    foreach($this->_child_boxes as $box) 
    {
      if(!$box->render($tcpdf)) { return false; }
    }

    return true;
  }
}

class SurveyInfoBox extends PDFBox
{
  private PDFBox $_box;
  private bool $_new_group = false;
  /**
   * @param TCPDF $tcpdf 
   * @param float $max_width
   * @param array $question 
   * @return void 
   */
  public function __construct(TCPDF $tcpdf, float $max_width, array $question)
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
   * Computes the layout of the actual info box
   * @param TCPDF $tcpdf 
   * @return void 
   */
  public function computeLayout(TCPDF $tcpdf)
  {
    $this->_box->setPosition($this->_page, $this->_x, $this->_y);
  }

  /**
   * Renders the content of a SurveyInfo box
   * @param TCPDF $tcpdf 
   * @return bool 
   */
  protected function render(TCPDF $tcpdf): bool
  {
    //$tcpdf->Rect($this->_x,$this->_y,$this->_width,$this->_height);
    return $this->_box->render($tcpdf);
  }
}

class SurveyFreetextBox extends PDFBox
{
  private ?PDFBox $_intro_box = null;
  private PDFBox  $_wording_box;
  private array   $_entry_box = [0,0,0,3*K_QUARTER_INCH];

  private float $_gap = 1; // mm

  /**
   * @param TCPDF $tcpdf 
   * @param float $max_width 
   * @param array $question 
   * @return void 
   */
  public function __construct(TCPDF $tcpdf, float $width, array $question)
  {
    parent::__construct($tcpdf);

    $wording = $question['wording'];
    $intro   = $question['intro'] ?? null;

    $this->_width = $width;
    $this->_height = 0;

    if ($intro) {
      if (possibleMarkdown($intro)) {
        $this->_intro_box = new PDFMarkdownBox($tcpdf, $width, $intro);
      } else {
        $this->_intro_box = new PDFTextBox($tcpdf, $width, $intro, multi: true);
      }
      $this->_height += $this->_intro_box->getHeight() + $this->_gap;
      $width -= K_QUARTER_INCH;
    }

    $this->_wording_box = new PDFTextBox($tcpdf,$width,$wording);
    $this->_height += $this->_wording_box->getHeight();

    $this->_entry_box[2] = $width;
    $this->_height += $this->_entry_box[3];
  }

  /**
   * Computes the layout of the intro/wording/entry boxes
   * @param TCPDF $tcpdf 
   * @return void 
   */
  public function computeLayout(TCPDF $tcpdf)
  {
    $x = $this->_x;
    $y = $this->_y;

    if($this->_intro_box) {
      $this->_intro_box->setPosition($this->_page, $x, $y);
      $y += $this->_intro_box->getHeight() + $this->_gap;
      $x += K_QUARTER_INCH;
    }
    $this->_wording_box->setPosition($this->_page, $x, $y);
    $y += $this->_wording_box->getHeight();

    $this->_entry_box[0] = $x;
    $this->_entry_box[1] = $y;
  }

  /**
   * Renders the content of a free text box
   * @param TCPDF $tcpdf 
   * @return bool 
   */
  protected function render(TCPDF $tcpdf) : bool
  {
    if($this->_intro_box) {
      if(!$this->_intro_box->render($tcpdf)) { return false; }
    }
    if(!$this->_wording_box->render($tcpdf)) { return false; }
    $tcpdf->setLineWidth(0.2);
    $tcpdf->Rect(...$this->_entry_box);

    return true;
  }
}

