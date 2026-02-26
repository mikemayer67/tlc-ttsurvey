<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file("include/db.php"));

// In addition to the original uploaded image, the ImageRegistry keeps track
//   of derivative images:
//   - resampled to smaller filesize
//   - converted for use in PDF (no transparency, jpeg) 
// The resampled images are sized such that the greater dimension of width
//   and height is a power of 2.  These are indexed by the specific power of 2.

class ImageResource
{
  private int    $_image_id;
  private string $_filename;
  private string $_mime_type;
  private int    $_width;
  private int    $_height;
  private int    $_uploaded; // unix time
  private int    $_modified; // unix time

  // derivative image sizes
  // - indexed by usage (pdf or web) and log2(max_dimension)
  // - contains a 2 element array [width,height]
  private array $_derivatives = [];

  /**
   * Image constructor from database query
   * @param int $image_id Image ID to look up in the database
   * @return void 
   */
  public function __construct(int $image_id)
  {
    $query = <<<SQL
      SELECT (
        filename, mime_type, width, height, 
        UNIX_TIMESTAMP(uploaded) as uploaded, 
        UNIX_TIMESTAMP(modified) as modified
      ) 
      FROM tlc_tt_images 
      WHERE image_id=?
    SQL;

    $rows = MySQLSelectRows($query,'i',$image_id);
    if(!$rows) { internal_error("ImageResource constructor called with invalid image id"); }

    $row = $rows[0];
    $this->_image_id   = $image_id;
    $this->_filename   = array_get_required('filename',  $row);
    $this->_mime_type  = array_get_required('mime_type',$row);
    $this->_width      = array_get_required('width',     $row);
    $this->_height     = array_get_required('height',    $row);
    $this->_uploaded   = array_get_required('uploaded',  $row);
    $this->_modified   = array_get_required('modified',  $row);

    $query = <<<SQL
      SELECT ( usage_key, size_key, width, height )
      FROM tlc_tt_image_derivatives
      WHERE image_id=?
    SQL;
    $rows = MySQLSelectRows($query,'i',$image_id);
    foreach($rows as $row) {
      $usage    = $row['usage_key'];
      $size_key = $row['size_key'];
      $width    = $row['width'];
      $height   = $row['height'];

      $this->_derivatives[$usage][$size_key] = [$width,$height];
    }
  }

//  /**
//   * Add derivative image file to registry and database
//   * @param string $usage
//   * @param int $size_key log2(maximum dimension) of the derivative image
//   * @param int $width actual width of the derivative image
//   * @param int $height actual height of the derivative image
//   * @param string $filename 
//   * @return void 
//   */
//  private function add_derivative(string $usage, int $size_key, int $width, int $height, string $filename)
//  {
//    $image_id = $this->_image_id;
//    if(isset($this->_derivatives[$usage][$size_key])) {
//      internal_error("Attempted to overwrite existing $size_key derivative for $usage image $image_id");
//    }
//    $this->_derivatives[$usage][$size_key] = $filename;
//
//    $query = <<<SQL
//      INSERT into tlc_tt_image_derivatives 
//        (image_id, usage_key, size_key, width, height, filename)
//        values (?,?,?,?,?,?)
//    SQL;
//    $rows = MySQLExecute($query,'isiiis',$image_id,$usage,$size_key,$width,$height,$filename);
//    if(count($rows) !== 1) {
//      internal_error("Attempted to overwrite existing $size_key derivatve for $usage image $image_id");
//    }
//  }

  /**
   * Looks up the path to the image file of the requested size
   *   Triggers generation of the derivative image file if necessary
   * @param string $usage 
   * @param null|int $size_key log2(maximum dimension) of the derivative image
   * @return string filename
   */
  public function app_file(string $usage, float $width=0, float $height=0) : string
  {
    if($size_key === null) { return $this->_filename; }
    return $this->_derivatives[$usage][$size_key] ?? null;
  }

  /**
   * Determines the size key for the desired width, height, or both
   * @param float $width desired width
   * @param float $height desired height
   * @return null|int log2(maximum dimension)
   */
  public function get_size_key(float $width=0, float $height=0) : ?int
  {
    if( $width  > $this->_width/2  ) { return null; }
    if( $height > $this->_height/2 ) { return null; }
    if( ($width === null) && ($height === null) ) { return null; }

    $aspect_ratio = $this->_height / $this->_width;
    $Nw = $width  ? ceil(log( $width * min(1,$aspect_ratio), 2)) : null;
    $Nh = $height ? ceil(log($height / max(1,$aspect_ratio), 2)) : null;
    return max($Nw,$Nh);
  }

}

class ImageLibrary
{

}