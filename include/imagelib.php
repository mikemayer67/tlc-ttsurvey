<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

/**
 * Library of all uploaded image files
 * @package tlc\tts
 */
class ImageLibrary
{
  /**
   * Returns name and dimensions of all image files in img/uploads folder
   * @return array<int, array{name:string, width:int, height:int, type:int}>
   */
  public static function all_images() : array
  {
    $images = [];

    $files = glob(app_file('img/uploads/*')) ?: [];
    foreach($files as $imagefile) {
      $info = self::lookup_image($imagefile);
      if($info) { $images[] = $info; }
    }
    return $images;
  }

  /**
   * Looks up information about a specified filename in the img/uploads folder
   * @param string $filename 
   * @return null|array{name:string, width:int, height:int, type:int}
   */
  public static function lookup_image(string $filename) : ?array
  {
    $info = getimagesize($filename);
    if($info) {
      return [
        'name' => basename($filename),
        'width' => $info[0],
        'height' => $info[1],
        'type' => $info[2],
      ];
    }
    return null;
  }

  /**
   * Locate the derivative logo file
   *   If pdf is specified, only looks for the jpg version (no transparency)
   *   Otherwise, looks for first png and then jpg version
   *   If neither are found in the img folder, returns null
   * @param bool $pdf 
   * @return null|string 
   */
  private static function app_logo(bool $pdf = false) : ?string {
    if(!$pdf) {
      $png_file = app_file('img/app_logo.png');
      if(file_exists($png_file)) { return 'img/app_logo.png'; }
    }
    $jpg_file = app_file('img/app_logo.jpg');
    if (file_exists($jpg_file)) { return 'img/app_logo.jpg'; }
    return null;
  }

  /**
   * Returns the filepath the app logo file.
   *   If pdf is specified, only looks for the jpg version (no transparency)
   *   Otherwise, looks for first png and then jpg version
   *   If neither are found in the img folder, returns null
   * @param bool $pdf 
   * @return null|string 
   */
  public static function app_logo_file(bool $pdf) : ?string {
    $logo = self::app_logo($pdf);
    return $logo ? app_file($logo) : null;
  }

  /**
   * Returns the app logo URI
   *   If pdf is specified, only looks for the jpg version (no transparency)
   *   Otherwise, looks for first png and then jpg version
   *   If neither are found in the img folder, returns null
   * @param bool $pdf 
   * @return null|string 
   */
  public static function app_logo_uri(bool $pdf) : ?string {
    $logo = self::app_logo($pdf);
    return $logo ? app_uri($logo) : null;
  }
}