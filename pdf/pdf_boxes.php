<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/ttpdf.php'));
require_once(app_file('pdf/ttpdf_utils.php'));

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
  protected TTPDF $_ttpdf;

  protected const debug = false;

  // Each of the following properties must be explicitly set in the box subclass
  //   The following are set in the subclass constructor
  protected float $_height     = 0;  // height of the box as it lays out on the PDF page
  protected float $_width      = 0;  // width of the box as it lays out on the PDF page
  protected float $_top_pad    = 0;  // required padding between this box and prior box
  protected float $_bottom_pad = 0;  // required padding between this box and next box

  // Only set on boxes that start a new page
  protected static int $_cur_page = 0;
  protected int        $_page     = 0;

  // The following are set in the call to position()
  protected float $_x = 0; // x location of the upper left corner of the box on the page
  protected float $_y = 0; // y location of the upper left corner of the box on the page

  /**
   * Returns the height of the box on the PDF page
   * @return float
   */
  public function getHeight(): float { return $this->_height; }

  /**
   * Returns the width of the box on the PDF page
   * @return float
   */
  public function getWidth(): float { return $this->_width; }

  /**
   * Resizes the width and/or height, but only if it makes that dimension larger.
   * @param float|null $width 
   * @param float|null $height 
   * @return void 
   */
  protected function grow(?float $width=null, ?float $height=null)
  {
    if($width !== null)  { $this->_width  = max($this->_width,  $width);  }
    if($height !== null) { $this->_height = max($this->_height, $height); }
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
    return max($this->_top_pad, $prior->_bottom_pad);
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
    self::$_cur_page += 1;
    $this->_page = self::$_cur_page;
  }

  /**
   * @return int number of pages in the PDF document
   */
  public static function numPages() : int {return self::$_cur_page; }

  /**
   * Positions the box and its children. 
   * - Must be overridden in child classes
   *   - should include call to super::position($x,$y)
   * - Must be called before render()
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function position(float $x, float $y)
  {
    $this->_x    = $x;
    $this->_y    = $y;
  }

  /**
   * Returns whether or not this box starts a new page
   * @return bool 
   */
  protected function isNewPage() : bool
  {
    return $this->_page > 0;
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
    $this->_ttpdf = $ttpdf;
  }

  /**
   * Kicks off the rendering of the box to the PDF output. 
   *   This method should be overridden by subclasses.
   *   Child classes should include call to parent::render()
   * @return bool indicates success/failure of the rendering
   */
  protected function render(): bool
  {
    if(self::debug) {
      $outline_color = $this->debug_color();
      if ($outline_color) {
        $this->_ttpdf->setDrawColor(...$outline_color);
        $this->_ttpdf->Rect($this->_x, $this->_y, $this->_width, $this->_height);
        $this->_ttpdf->setDrawColor(0);
      }
    }
    return true;
  }
  
  /**
   * Used for debugging layout 
   *   Sets the color to be used to draw outlines around boxes.
   *   Override this in any PDFBox subclass that needs visual laydown debugging
   *   Default value is to not draw an outline.
   * @return array (see TCPDF::setDrawColor for details)
   */
  protected function debug_color() : array
  {
    return [];
  }
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
  /** @var PDFBox[] $_children */
  private array $_children = [];
  private float $_content_left = 0;
  private float $_content_right = 0;
  private float $_content_top = 0;
  private float $_content_bottom = 0;

  private const bottom_pad = K_EIGHTH_INCH;

  /**
   * Constructor does nothing but invokes the PDFBox constructor.
   *   While the existence of this method is not strictly necessary, it serves as
   *   a reminder that all subclasses of PDFRootBox should also invoke the parent constrctor
   * @param TTPDF instances of a TCDPF class (or subclass)
   * @return void 
   */
  public function __construct(TTPDF $ttpdf)
  {
    parent::__construct($ttpdf);

    $this->_content_left   = PDF_MARGIN_LEFT;
    $this->_content_right  = $ttpdf->getPageWidth() - PDF_MARGIN_RIGHT;
    $this->_content_top    = PDF_MARGIN_TOP;
    $this->_content_bottom = $ttpdf->getPageHeight() - (PDF_MARGIN_BOTTOM + self::bottom_pad);
  }

  public function content_width()  : float { return $this->_content_right  - $this->_content_left; }
  public function content_height() : float { return $this->_content_bottom - $this->_content_top;  }

  /**
   * Adds a child box to the root box.
   * @param PDFBox $child 
   * @return void 
   */
  protected function addChild(PDFBox $child): void
  {
    $this->_children[] = $child;
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
    $cur_y  = $this->_content_top;

    foreach ($this->_children as $box) 
    {
      if ($box->resetIndent()) { $indent = 0; }

      $max_y = PDF_MARGIN_TOP + $this->content_height() * $box->maxPagePos();

      $cur_y += $box->yOffset($prior);
      $prior = $box;

      if (PDFBox::numPages() === 0 || ($cur_y > $max_y) || ($cur_y + $box->getHeight() > $this->_content_bottom)) {
        $box->startPage();
        $cur_y = $this->_content_top;
      }

      $box->position($this->_content_left + $indent, $cur_y);

      $cur_y  += $box->getHeight();
      $indent += $box->incrementIndent();
    }
  }

  /**
   * Controls the rendering of all child boxes.
   *   This method should not need to be overwritten by subclassses of PDFRootBox
   * @return bool 
   */
  public function render(): bool
  {
    foreach ($this->_children as $child) {
      if ($child->isNewPage()) { $this->_ttpdf->AddPage(); }
      if (!$this->render_child($child)) { return false; }
    }
    return true;
  }

  /**
   * Renders a single child box
   *   Subclasses that need to do more than simply render the child to the
   *   page should overload this class. 
   * @param PDFBox $child 
   * @return bool 
   */
  protected function render_child(PDFBox $child) : bool
  {
    return $child->render();
  }
}

/**
 * PDFTextBox provides for rendering single or multiline text
 */
class PDFTextBox extends PDFBox
{
  private string $_text = '';   // text to be rendered
  private string $_family = ''; // font family
  private string $_style = '';  // font style
  private float $_size = 0;     // fontn size

  private bool $_multi = false;

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

    $this->_family = $family ? $family : K_SANS_SERIF_FONT;
    $this->_style = $style;
    $this->_size = $factor * ($size ? $size : K_DEFAULT_FONT_SIZE);

    $ttpdf->setFont($this->_family, $this->_style, $this->_size);

    if ($multi) {
      $this->_text = $text;
      $this->_width = $w;
      $num_lines = $ttpdf->getNumLines($text, $w);
      $this->_multi = $num_lines > 1;
      $this->_height = $num_lines * ttpdf_line_height($ttpdf);
    } else {
      $padding = $ttpdf->getCellPaddings();
      $this->_text = ttpdf_truncate_text($ttpdf, $text, $w);
      $this->_width = $ttpdf->GetStringWidth($this->_text) + $padding['L'] + $padding['R'];
      $this->_height = ttpdf_line_height($ttpdf);
    }
  }

  /**
   * Renders the PDFTextBox
   * @return bool 
   */
  protected function render(): bool
  {
    if (!parent::render()) { return false; }
    //$ttpdf->Rect($this->_x,$this->_y,$this->_max_width,$this->_height);
    //$ttpdf->Rect($this->_x,$this->_y,$this->_width,$this->_height);
    $this->_ttpdf->setFont($this->_family, $this->_style, $this->_size);
    $this->_ttpdf->setY($this->_y);
    $this->_ttpdf->setX($this->_x);
    if ($this->_multi) {
      $this->_ttpdf->MultiCell($this->_width, $this->_height, $this->_text, align:'L');
    } else {
      $this->_ttpdf->Cell($this->_width, $this->_height, $this->_text);
    }
    return true;
  }
}

/**
 * Provides rendering of text that contains markdown
 */
class PDFMarkdownBox extends PDFBox
{
  private string $_html = '';   // text to be rendered
  private string $_family = ''; // font family
  private float $_size = 0;     // fontn size

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

    $this->_family = $family ? $family : K_SANS_SERIF_FONT;
    $this->_size = $factor * ($size ? $size : K_DEFAULT_FONT_SIZE);

    $ttpdf->setFont($this->_family, '', $this->_size);

    $this->_width = $w;

    $this->_html = MarkdownParser::parse($markdown, false);

    $startY = 0;  // arbitrary, but 0 is as good as any other number and gives us the most working room
    $ttpdf->startTransaction();
    $ttpdf->AddPage();
    $ttpdf->writeHTMLCell($w, 0, 0, $startY, $this->_html, ln: 1);
    $this->_height = $ttpdf->GetY() - $startY;
    $ttpdf->rollbackTransaction(true);
  }

  /**
   * Renders a PDFMarkdownBox
   * @return bool 
   */
  protected function render(): bool
  {
    if (!parent::render()) { return false; }
    $this->_ttpdf->setFont($this->_family, '', $this->_size);
    $this->_ttpdf->writeHTMLCell($this->_width, $this->_height, $this->_x, $this->_y,$this->_html);
    return true;
  }
}
