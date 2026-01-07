<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

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
 * @method bool startsNewPage()
 */
abstract class PDFBox 
{
  // The following is set in the PDFBox constructor
  protected TCPDF $_tcpdf; // instance of the TCPDF class or a subclass thereof
  // Each of the following properties must be explicitly set in the box subclass
  //   The following are set in the subclass constructor
  protected float $_height  = 0;  // height of the box as it lays out on the PDF page
  protected float $_width   = 0;  // width of the box as it lays out on the PDF page
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
   * Returns whether or not the box type requires starting a new page.
   *   Note that this is not the same as a box starting a new page due to
   *   layout of the boxes within the PDF file.
   * This method should be overwritten in subclasses as needed.
   * @return bool 
   */
  public function startsNewPage() : bool { return false; }

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
    foreach($this->_children as $indx=>$child) {
      if($indx===0 || $child->startsNewPage()) { $this->_tcpdf->AddPage(); }
      $rc = $child->render();
      if(!$rc) return false;
    }
    return true;
  }
}