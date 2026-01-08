<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/tcpdf_utils.php'));

use \TCPDF;

/**
 * PDFBox is the baseclass for wrapping methods used to compute and render Boxes of content
 *   in a PDF file using the TCPDF package.  This class provides an abstract interface that must be
 *   subclassed for each flavor of box.  It provides common functionality applicable to each of
 *   the subclasses.  
 * It is anticipated that boxes will contain other boxes into a nested laydown structure.
 * 
 * @method float getHeight()
 * @method float getWidth()
 * @method float maxPagePos()
 */
abstract class PDFBox 
{
  // The following is set in the PDFBox constructor
  protected TCPDF $_tcpdf; // instance of the TCPDF class or a subclass thereof
  // Each of the following properties must be explicitly set in the box subclass
  //   The following are set in the subclass constructor
  protected float $_height  = 0;  // height of the box as it lays out on the PDF page
  protected float $_width   = 0;  // width of the box as it lays out on the PDF page
  protected array $_margin  = [0,0,0,0];  // same order as CSS: TRBL
  //   The following are set in setPosition
  protected int   $_page = 0;
  protected float $_x    = 0; // x location of the upper left corner of the box on the page
  protected float $_y    = 0; // y location of the upper left corner of the box on the page

  /**
   * Returns the height of the box on the PDF page
   * @return float
   */
  public function getHeight() : float { return $this->_height; }

  /**
   * Returns the width of the box on the PDF page
   * @return float
   */
  public function getWidth() : float { return $this->_width; }

  /**
   * Returns the maximum fractional Y position down the page at which this box
   *   can be rendered.
   * This method should be overwritten in subclasses as needed.
   * @return float 
   */
  public function maxPagePos() : float { return 1; }

  /**
   * @internal Used to define the position of the box.  Must be called before render()
   * @param int $page 
   * @param float $x 
   * @param float $y 
   * @return void 
   */
  protected function setPosition(int $page,float $x,float $y)
  {
    $this->_page = $page;
    $this->_x    = $x;
    $this->_y    = $y;
  }

  /**
   * Returns whether or not this box starts a new page
   * @param int $curPage 
   * @return bool 
   */
  protected function isNewPage(int $curPage)
  {
    return $this->_page > $curPage;
  }

  /**
   * Constructor does nothing but set the pointer to the TCPDF instance
   *   All subclasses should invoke the parent constructor to ensure this is set up correctly
   * @param TCPDF instances of a TCDPF class (or subclass)
   * @return void 
   */
  public function __construct(TCPDF $tcpdf)
  {
    $this->_tcpdf = $tcpdf;
  }

  /**
   * Kicks off the rendering of the box to the PDF output. This method should be overridden
   *   by subclasses.
   * @return bool indicates success/failure of the rendering
   */
  abstract protected function render() : bool;
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

  /**
   * Constructor does nothing but invokes the PDFBox constructor.
   *   While the existence of this method is not strictly necessary, it seves as
   *   a reminder that all subclasses of PDFRootBox must also invoke the parent constrctor
   * @param TCPDF instances of a TCDPF class (or subclass)
   * @return void 
   */
  public function __construct(TCPDF $tcpdf)
  {
    parent::__construct($tcpdf);
  }

  /**
   * Adds a child box to the root box.
   * @param PDFBox $child 
   * @return void 
   */
  protected function addChild(PDFBox $child) : void
  {
    $this->_children[] = $child;
  }

  /**
   * 
   * @return bool 
   */
  public function render() : bool 
  {
    $last_page = 0;
    foreach($this->_children as $child) {
      if($child->isNewPage($last_page)) { $this->_tcpdf->AddPage(); }
      $rc = $child->render();
      if(!$rc) return false;
    }
    return true;
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
     * @param TCPDF $tcpdf 
     * @param float $w max allowable width of the text cell, final width may be less
     * @param string $text string to add to the form
     * @param string $family font family to use, defaults to the sans-serif font
     * @param string $style, font face, defaults to normal
     * @param float $size font size, defaults to K_DEFAULT_FONT_SIZE
     * @param float $factor font size scaling factor, defaults to 1
     * @param bool $multi indicates if text wrapping is permissible
     * @return void 
     */
    public function __construct(TCPDF $tcpdf,float $w, string $text,
        string $family='', string $style='', float $size=0, float $factor=1, 
        bool $multi=false )
    {
        parent::__construct($tcpdf);
        
        $this->_family = $family ? $family : K_SANS_SERIF_FONT;
        $this->_style = $style;
        $this->_size = $factor * ($size ? $size : K_DEFAULT_FONT_SIZE);

        $tcpdf->setFont($this->_family, $this->_style, $this->_size);

        if($multi)
        {
          $this->_text = $text;
          $this->_width = $w;
          $num_lines = $tcpdf->getNumLines($text,$w);
          $this->_multi = $num_lines > 1;
          $this->_height = $num_lines * tcpdf_line_height($tcpdf);
        } else {
          $this->_text = tcpdf_truncate_text($tcpdf,$text,$w);
          $this->_width = $tcpdf->GetStringWidth($this->_text);
          $this->_height = tcpdf_line_height($tcpdf);
        }
    }

    protected function render() : bool {
        // @@@TODO Flesh this out
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
     * @param TCPDF $tcpdf 
     * @param float $w width of the markdown box 
     * @param string $markdown markdown text
     * @param string $family font family
     * @param float $size font size
     * @param float $factor font scaling factor
     * @return void 
     */
    public function __construct(TCPDF $tcpdf, float $w, string $markdown, 
      string $family='', float $size=0, float $factor=1 )
    {
        parent::__construct($tcpdf);

        $this->_family = $family ? $family : K_SANS_SERIF_FONT;
        $this->_size = $factor * ($size ? $size : K_DEFAULT_FONT_SIZE);

        $tcpdf->setFont($this->_family, '', $this->_size);

        $this->_width = $w;

        $this->_html = MarkdownParser::parse($markdown, false);

        $startY = 0;  // arbitrary, but 0 is as good as any other number and gives us the most working room
        $tcpdf->startTransaction();
        $tcpdf->AddPage();
        $tcpdf->writeHTMLCell($w,0, 0, $startY, $this->_html, ln:1);
        $this->_height = $tcpdf->GetY() - $startY;
        $tcpdf->rollbackTransaction(true);
    }

    protected function render(): bool
    {
      // @@@TODO Flesh this out
      return true;
    }
}
