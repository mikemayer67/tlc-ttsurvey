<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));

class QuestionFlags {
  const MASK_LEFT_RIGHT = 0x0001;  // 0:LEFT  1:RIGHT
  const MASK_ROW_COL    = 0x0002;  // 0:ROW   1:COLUMN
  const MASK_HAS_OTHER  = 0x0004;  // boolean

  private int $bits = 0;

  public function __construct($bits=0) {
    $this->bits = $bits;
  }

  public function get_bits(): int 
  {
    return $this->bits;
  }

  /**
   * Getter/Setter for right alignment
   * @param null|bool $value (null=getter, bool=setter)
   * @return null|bool setter:null, getter:bool
   */
  public function align_right(?bool $value=null) : ?bool
  {
    if($value === null) { // getter
      return ($this->bits & self::MASK_LEFT_RIGHT) === self::MASK_LEFT_RIGHT;
    } 
    elseif($value) { // set align right
      $this->bits |= self::MASK_LEFT_RIGHT;
    } 
    else { // clear align right
      $this->bits &= ~self::MASK_LEFT_RIGHT;
    }
    return null;
  }

  /**
   * Getter/Setter for left alignment
   * @param null|bool $value (null=getter, bool=setter)
   * @return null|bool setter:null, getter:bool
   */
  public function align_left(?bool $value=null) : ?bool
  {
    if($value === null) { return !$this->align_right(); }
    else                { $this->align_right(!$value);  }
    return null;
  }

  /**
   * Getter/Setter for column orientation
   * @param null|bool $value (null=getter, bool=setter)
   * @return null|bool setter:null, getter:bool
   */
  public function orient_column(?bool $value=null) : ?bool
  {
    if($value === null) { // getter
      return ($this->bits & self::MASK_ROW_COL) === self::MASK_ROW_COL;
    } 
    elseif($value) { // set align right
      $this->bits |= self::MASK_ROW_COL;
    } 
    else { // clear align right
      $this->bits &= ~self::MASK_ROW_COL;
    }
    return null;
  }

  /**
   * Getter/Setter for row orientation
   * @param null|bool $value (null=getter, bool=setter)
   * @return null|bool setter:null, getter:bool
   */
  public function orient_row(?bool $value=null) : ?bool
  {
    if($value === null) { return !$this->orient_column(); }
    else                { $this->orient_column(!$value);  }
    return null;
  }

  /**
   * Getter/Setter for "has other"
   * @param null|bool $value (null=getter, bool=setter)
   * @return null|bool setter:null, getter:bool
   */
  public function has_other(?bool $value=null) : ?bool
  {
    if($value === null) { // getter
      return ($this->bits & self::MASK_HAS_OTHER) === self::MASK_HAS_OTHER;
    } 
    elseif($value) { // set align right
      $this->bits |= self::MASK_HAS_OTHER;
    } 
    else { // clear align right
      $this->bits &= ~self::MASK_HAS_OTHER;
    }
    return null;
  }

  public function layout(string $context, ?string $value=null) : ?string
  {
    if( $value === null ) {
      //this is the getter
      switch(strtoupper($context)) {
      case "BOOL":
        return $this->align_right() ? "RIGHT" : "LEFT";
        break;
      case "SELECT_ONE":
      case "SELECT_MULTI":
        return ( 
          $this->orient_row() ? "ROW" :
          ($this->align_right() ? "RCOL" : "LCOL")
        );
        break;
      default:
        return null;
        break;
      }
    }
    // this is the setter
    $value = strtoupper($value);
    $this->orient_column( in_array($value, ["RCOL","LCOL"] , true) );
    $this->align_right(   in_array($value, ["RCOL","RIGHT"], true) );
    return null;
  }
}
