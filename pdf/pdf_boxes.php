<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/ttpdf.php'));
require_once(app_file('pdf/ttpdf_utils.php'));
require_once(app_file('survey/markdown.php'));

/**
 * PDFBox is the baseclass for wrapping methods used to compute and render Boxes of content
 *   in a PDF file using the TTPDF package.  This class provides an abstract interface that must be
 *   subclassed for each flavor of box.  It provides common functionality applicable to each of
 *   the subclasses.  
 * It is anticipated that boxes will contain other boxes into a nested laydown structure.
 * 
 * @method float getHeight()
 * @method float getWidth()
 * @method void  grow(?float $width,?float $height)
 * @method float maxPagePos()
 */
abstract class PDFBox
{
  protected TTPDF $ttpdf;

  protected const debug = false;

  // Each of the following properties must be explicitly set in the box subclass
  //   The following are set in the subclass constructor
  protected float $height     = 0;  // height of the box as it lays out on the PDF page
  protected float $width      = 0;  // width of the box as it lays out on the PDF page
  protected float $top_pad    = 0;  // required padding between this box and prior box
  protected float $bottom_pad = 0;  // required padding between this box and next box

  // Only set on boxes that start a new page
  protected static int $cur_page = 0;
  protected int        $page     = 0;

  // The following are set in the call to position()
  protected float $x = 0; // x location of the upper left corner of the box on the page
  protected float $y = 0; // y location of the upper left corner of the box on the page

  /**
   * Returns the height of the box on the PDF page
   * @return float
   */
  public function getHeight(): float { return $this->height; }

  /**
   * Returns the width of the box on the PDF page
   * @return float
   */
  public function getWidth(): float { return $this->width; }

  /**
   * Resizes the width and/or height, but only if it makes that dimension larger.
   * @param float|null $width 
   * @param float|null $height 
   * @return void 
   */
  protected function grow(?float $width=null, ?float $height=null)
  {
    if($width !== null)  { $this->width  = max($this->width,  $width);  }
    if($height !== null) { $this->height = max($this->height, $height); }
  }

  /**
   * Returns the maximum fractional Y position down the page at which this box
   *   can be rendered.
   * This method should be overwritten in subclasses as needed.
   * @return float 
   */
  public function maxPagePos(): float { return 1; }

  /**
   * Returns the vertical offset from the prior box (or 0 if no prior)
   * @param PDFBox $prior 
   * @return float 
   */
  public function yOffset(?PDFBox $prior): float
  {
    if (is_null($prior)) { return 0; }
    return max($this->top_pad, $prior->bottom_pad);
  }

  /**
   * Returns whether the indent should be reset to 0
   *   Default = false
   *   Subclasses which reset the indent should override this method
   * @return bool 
   */
  public function resetIndent(): bool { return false; }

  /**
   * Returns amount by which subsequent boxes should be at an increased indent
   *   Default = 0
   *   Subclasses which increment indent should override this method
   * @return float amount by which to increment the indent
   */
  public function incrementIndent(): float { return 0; }

  
  /**
   * Sets the page number on boxes that start a new page
   * @param int $page 
   * @return void 
   */
  protected function startPage() 
  {
    self::$cur_page += 1;
    $this->page = self::$cur_page;
  }

  /**
   * @return int number of pages in the PDF document
   */
  public static function numPages() : int {return self::$cur_page; }

  /**
   * Positions the box and its children. 
   * - Must be overridden in child classes
   *   - should include call to super::position($x,$y)
   * - Must be called before render()
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return bool 
   */
  protected function position(float $x, float $y) : bool
  {
    $this->x = $x;
    $this->y = $y;
    return true;
  }

  /**
   * Returns whether or not this box starts a new page
   * @return bool 
   */
  protected function isNewPage() : bool
  {
    return $this->page > 0;
  }

  /**
   * Returns the section label to appear in the page footer
   *   Should be overridden in subclasses that modify the current section
   * @return null|string 
   */
  protected function currentSection() : ?string { return null; }

  /**
   * Constructor currently does nothing other than set the minimum required argument list
   * @param TTPDF instances of a TCDPF class (or subclass)
   * @return void 
   */
  public function __construct(TTPDF $ttpdf) 
  {
    $this->ttpdf = $ttpdf;
  }

  /**
   * Kicks off the rendering of the box to the PDF output. 
   *   This method should be overridden by subclasses.
   *   Child classes should include call to parent::render()
   * @return bool void
   */
  protected function render()
  {
    if(self::debug) {
      $outline_color = $this->debug_color();
      if ($outline_color) {
        $this->ttpdf->setDrawColor(...$outline_color);
        $this->ttpdf->Rect($this->x, $this->y, $this->width, $this->height);
        $this->ttpdf->setDrawColor(0);
      }
    }
  }
  
  /**
   * Used for debugging layout 
   *   Sets the color to be used to draw outlines around boxes.
   *   Override this in any PDFBox subclass that needs visual laydown debugging
   *   Default value is to not draw an outline.
   * @return array (see TCPDF::setDrawColor for details)
   */
  protected function debug_color() : array { return []; }
}

/**
 * PDFRootBox is also an abstract class that is intended to contain all top level boxes
 *   that will be rendered.  Note that some boxes may constain subboxes.  In that case, it
 *   is the responsiblity of the containing class to manage its children.  This class only
 *   manages the top level boxes.
 * PDFRootBox provides functionality that is form agnostic.  The form specific details 
 *   (such as parsing the content data structure into boxes) must be handled
 *   by subclassing it.
 */
abstract class PDFRootBox extends PDFBox
{
  // This list of all top level child boxes
  /** @var PDFBox[] $children */
  private array $children = [];
  private float $content_left = 0;
  private float $content_right = 0;
  private float $content_top = 0;
  private float $content_bottom = 0;

  private const bottom_pad = K_EIGHTH_INCH;

  /**
   * Constructor does nothing but invokes the PDFBox constructor.
   *   While the existence of this method is not strictly necessary, it serves as
   *   a reminder that all subclasses of PDFRootBox should also invoke the parent constrctor
   * @param TTPDF instances of a TCDPF class (or subclass)
   * @param float $top margin (default=PDF_MARGIN_TOP)
   * @param float $right margin (default=PDF_MARGIN_RIGHT)
   * @param float $bottom margin (default=PDF_MARGIN_BOTTOM)
   * @param float $left margin (default=PDF_MARGIN_LEFT)
   * @return void 
   */
  public function __construct(
    TTPDF $ttpdf, 
    float $top=PDF_MARGIN_TOP, 
    float $right=PDF_MARGIN_RIGHT,
    float $bottom=PDF_MARGIN_BOTTOM,
    float $left=PDF_MARGIN_LEFT
  ) {
    parent::__construct($ttpdf);

    $this->content_left   = $left;
    $this->content_right  = $ttpdf->getPageWidth() - $right;
    $this->content_top    = $top;
    $this->content_bottom = $ttpdf->getPageHeight() - ($bottom + self::bottom_pad);
  }

  public function content_width()  : float { return $this->content_right  - $this->content_left; }
  public function content_height() : float { return $this->content_bottom - $this->content_top;  }

  /**
   * Adds a child box to the root box.
   * @param PDFBox $child 
   * @return void 
   */
  protected function addChild(PDFBox $child): void
  {
    $this->children[] = $child;
  }

  /**
   * Computes the layout of all toplevel boxes.
   *   This sets the page, x, and y location of each child box
   * @return void 
   */
  public function layoutChildren()
  {
    $prior = null;
    $indent = 0;
    $cur_y  = $this->content_top;

    foreach ($this->children as $box) 
    {
      if ($box->resetIndent()) { $indent = 0; }

      $max_y = PDF_MARGIN_TOP + $this->content_height() * $box->maxPagePos();

      $cur_y += $box->yOffset($prior);
      $prior = $box;

      if (PDFBox::numPages() === 0 || ($cur_y > $max_y) || ($cur_y + $box->getHeight() > $this->content_bottom)) {
        $box->startPage();
        $cur_y = $this->content_top;
      }

      if(!$box->position($this->content_left + $indent, $cur_y)) {
        $box->startPage();
        $cur_y = $this->content_top;
        $box->position($this->content_left + $indent, $cur_y);
      }

      $cur_y  += $box->getHeight();
      $indent += $box->incrementIndent();
    }
  }

  /**
   * Controls the rendering of all child boxes.
   *   This method should not need to be overwritten by subclassses of PDFRootBox
   * @return void 
   */
  public function render()
  {
    foreach ($this->children as $child) {
      if ($child->isNewPage()) { $this->ttpdf->AddPage(); }
      $this->render_child($child);
    }
  }

  /**
   * Renders a single child box
   *   Subclasses that need to do more than simply render the child to the
   *   page should overload this class. 
   * @param PDFBox $child 
   * @return void 
   */
  protected function render_child(PDFBox $child)
  {
    $child->render();
  }
}

/**
 * PDFTextBox provides for rendering single or multiline text
 */
class PDFTextBox extends PDFBox
{
  private string $text = '';   // text to be rendered
  private string $family = ''; // font family
  private string $style = '';  // font style
  private float  $size = 0;    // fontn size

  private int  $num_lines = 1;

  /**
   * PDFTextBox Constructor.  
   *   The box's final width and height will reflect the dimensions necessary to
   *   render the box content, not necessarily the specified width.  The width,
   *   however, will never exceed the specified width.
   * @param TTPDF $ttpdf 
   * @param float $w max allowable width of the text cell, final width may be less
   * @param string $text string to add to the form
   * @param string $family font family to use, defaults to the sans-serif font
   * @param string $style, font face, defaults to normal
   * @param float $size font size, defaults to K_DEFAULT_FONT_SIZE
   * @param float $factor font size scaling factor, defaults to 1
   * @param bool $multi indicates if text wrapping is permissible
   * @return void 
   */
  public function __construct(
    TTPDF $ttpdf,
    float $w,
    string $text,
    string $family = '',
    string $style = '',
    float $size = 0,
    float $factor = 1,
    bool $multi = false
  ) {
    parent::__construct($ttpdf);

    $this->family = $family ? $family : K_SANS_SERIF_FONT;
    $this->style = $style;
    $this->size = $factor * ($size ? $size : K_DEFAULT_FONT_SIZE);

    $ttpdf->setFont($this->family, $this->style, $this->size);

    if ($multi) {
      $this->text = $text;
      $this->width = $w;
      $this->num_lines = $ttpdf->getNumLines($text, $w);
      $this->height = $this->num_lines * ttpdf_line_height($ttpdf);
    } else {
      $padding = $ttpdf->getCellPaddings();
      $this->text = ttpdf_truncate_text($ttpdf, $text, $w);
      $this->width = $ttpdf->GetStringWidth($this->text) + $padding['L'] + $padding['R'];
      $this->height = ttpdf_line_height($ttpdf);
    }
  }

  /**
   * Number of lines of text in the box
   * @return int 
   */
  public function getNumLines() : int {
    return $this->num_lines;
  }

  /**
   * Height of a single line in the box
   * @return float 
   */
  public function getLineHeight() : float {
    return $this->height / $this->num_lines;
  }

  /**
   * Renders the PDFTextBox
   * @return void
   */
  protected function render()
  {
    parent::render();
    $this->ttpdf->setFont($this->family, $this->style, $this->size);
    $this->ttpdf->setY($this->y);
    $this->ttpdf->setX($this->x);
    if ($this->num_lines > 1) {
      $this->ttpdf->MultiCell($this->width, $this->height, $this->text, align:'L');
    } else {
      $this->ttpdf->Cell($this->width, $this->height, $this->text);
    }
  }

  protected function debug_color(): array { return [128,128,128]; }
}

/**
 * Provides rendering of text that contains markdown
 */
class PDFMarkdownBox extends PDFBox
{
  private string $html = '';   // text to be rendered
  private string $family = ''; // font family
  private float $size = 0;     // fontn size

  /**
   * PDFMarkdownBox Constructor.  
   *   The box's width will fill the full width specified.
   *   Note that the font style is not specified here as the ability to
   *     add bold and italic belongs to the markdown engine
   * @param TTPDF $ttpdf 
   * @param float $w width of the markdown box 
   * @param string $markdown markdown text
   * @param string $family font family
   * @param float $size font size
   * @param float $factor font scaling factor
   * @return void 
   */
  public function __construct(
    TTPDF $ttpdf,
    float $w,
    string $markdown,
    string $family = '',
    float $size = 0,
    float $factor = 1
  ) {
    parent::__construct($ttpdf);

    $this->family = $family ? $family : K_SANS_SERIF_FONT;
    $this->size = $factor * ($size ? $size : K_DEFAULT_FONT_SIZE);

    $ttpdf->setFont($this->family, '', $this->size);

    $this->width = $w;

    $this->html = MarkdownParser::parse($markdown, false);

    $startY = 0;  // arbitrary, but 0 is as good as any other number and gives us the most working room
    $ttpdf->startTransaction();
    $ttpdf->AddPage();
    $ttpdf->writeHTMLCell($w, 0, 0, $startY, $this->html, ln: 1);
    $this->height = $ttpdf->GetY() - $startY;
    $ttpdf->rollbackTransaction(true);
  }

  /**
   * Renders a PDFMarkdownBox
   * @return void 
   */
  protected function render()
  {
    parent::render();
    $this->ttpdf->setFont($this->family, '', $this->size);
    $this->ttpdf->writeHTMLCell($this->width, $this->height, $this->x, $this->y,$this->html);
  }
}
