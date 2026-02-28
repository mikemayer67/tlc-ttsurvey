<?php
namespace tlc\tts;

use RuntimeException;

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
    sort($files);
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
   * Returns the filepath the app logo file.
   * @return null|string 
   */
  public static function app_logo_file() : ?string {
    $logo_file = app_file('img/app_logo.jpg');
    return file_exists($logo_file) ? $logo_file : null;
  }

  /**
   * Returns the app logo URI
   * @return null|string 
   */
  public static function app_logo_uri() : ?string 
  {
    $logo = 'img/app_logo.jpg';
    return file_exists(app_file($logo)) ? $logo : null;
  }

  /**
   * Updates the current app logo using an existing or uploaded image file
   * If both $filename and $uploaded_file are specified, the latter takes precedence.
   * If neither is specifiied, the app's logo will be removed
   * If only a filename is specified:
   *   If no such image file exists, 
   *   - the app's logo will be removed and an error will be returned
   *   If it doesn't match the current logo filename
   *   - the app's logo will be updated to use the specified image file
   *   - if any error occurs while preparing the logo, an error with key 'app_logo' will be returned
   *   If it does matches the current logo filename
   *   - no change is made to the app's logo
   * If an uploaded image file is specified:
   *   - The file is moved from its uploaded location ($uploaded_file['tmp_name'])
   *     to the app's uploaded image file directory and prepared for usage as the app logo
   * - If any occurs during the move or preparation, an eror with the key 'app_logo' will be returned
   * @param string $filename existing image filename
   * @param array $upload newly uploaded file from $_FILES
   * @return array key/value array of errors encountered updating the logo
   */
  public static function update_app_logo(string $filename='', array $upload = []) : array
  {
    $logo_file  = app_file('img/app_logo.jpg');
    $image_file = null;

    if($upload['size']??0) {
      foreach (['tmp_name', 'name'] as $key) {
        if (!array_key_exists($key, $upload)) {
          log_error("Uploaded logo file info missing '$key'");
          return ['app_logo_file' => 'Failed to upload logo'];
        }
      }
      $old_path = $upload['tmp_name'];
      $filename = $upload['name'];
      $image_file = app_file("img/uploads/$filename");
      if (!move_uploaded_file($old_path, $image_file)) {
        log_error("Failed to move uploaded file from $old_path to $image_file");
        return ['app_logo_file' => 'Failed to upload logo'];
      }
    } 
    elseif($filename) {
      $image_file = app_file("img/uploads/$filename");
      if(!file_exists($image_file)) { 
        // requested image file does not exist
        return ['app_logo' => 'Missing image file'];
      }
      if($filename === get_setting('app_logo')) {
        // image file exists and hasn't changed. we're done here
        return [];
      }
    } else {
      unlink($logo_file);
      set_setting('app_logo',null);
      return [];
    }

    $result = self::create_jpeg($image_file, $logo_file, 150, 150, crop:false);
    if(!$result) {
      // Actual reason will be written to log as warning
      return ['app_logo' => 'Failed to create logo file'];
    }

    set_setting('app_logo', $filename);
    return [];
  }

  /**
   * Constructs a jpeg version of the specified image rescaled to the specified size
   * If both width and height are specified (and non-zero), the image will be cropped to fit
   * If width or height is specified, the other will be determined to keep aspect ratio
   * If neither is specified, the original size is retained
   * On failure, the reason will be logged as a warning
   * @param string $src 
   * @param string $dst 
   * @param int $width 
   * @param int $height 
   * @param int $qual
   * @param bool  $crop true:crop images, false:scale images
   * @return bool
   */
  public static function create_jpeg(
    string $src, string $dst, int $width=0, int $height=0, 
    int $qual=85, bool $crop=true
    ) : bool
  {
    $old_image = null;
    $new_image = null;
    $handler_set = false;

    $start_error_handler = function(string $prefix) use (&$handler_set) {
      if($handler_set) { restore_error_handler(); }
      set_error_handler(function($severity,$message) use ($prefix) {
        if($severity & E_WARNING) { throw new RuntimeException("$prefix: $message"); }
      });
      $handler_set = true;
    };

    try {
      $err_msg = "Failed to read source image $src";
      $start_error_handler($err_msg);

      $data = file_get_contents($src);
      if($data===false) { throw new RuntimeException($err_msg); }
      if(!$data)        { throw new RuntimeException("Appears to be empty"); }

      $err_msg = "Failed to load image using data from $src";
      $start_error_handler($err_msg);

      $old_image = imagecreatefromstring($data);
      if($old_image === false) throw new RuntimeException($err_msg);

      $ox = 0;
      $oy = 0;
      $ow = imagesx($old_image);
      $oh = imagesy($old_image);

      if($width>0 && $height>0) {
        if($crop) {
          $ar = $height/$width;
          $oy = max(0, ($oh - $ow*$ar)/2);
          $ox = max(0, ($ow - $oh/$ar)/2);
        }
      } elseif($width>0) {
        $height = $width * $ow/$oh;
      } elseif($height>0) {
        $width = $height * $oh/$ow;
      } else {
        $width = $ow;
        $height = $oh;
      }

      $err_msg = "Failed to create new image using data from $src";
      $start_error_handler($err_msg);

      $new_image = imagecreatetruecolor($width, $height);
      $white = imagecolorallocate($new_image, 255, 255, 255);
      imagefill($new_image,0,0,$white);

      imagecopyresampled($new_image, $old_image, 0, 0, $ox, $oy, $width, $height, $ow, $oh);

      $result = imagejpeg($new_image,$dst,$qual);
      if(!$result) { throw new RuntimeException($err_msg); }
    }
    catch(RuntimeException $e) {
      log_warning($e->getMessage());
      return false;
    }
    finally {
      if($handler_set) { restore_error_handler(); }
      if(is_resource($old_image)) { 
        /** @disregard P1007 */
        /** @disregard P1006 */
        @imagedestroy($old_image);
      }
      if(is_resource($new_image)) { 
        /** @disregard P1007 */
        /** @disregard P1006 */
        @imagedestroy($new_image);
      }
    }
    return true;
  }
}
