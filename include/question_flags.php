<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));

class QuestionFlags {
  const MASK_LEFT_RIGHT = 0x0001;  // 0:LEFT  1:RIGHT
  const MASK_ROW_COL    = 0x0002;  // 0:ROW   1:COLUMN
  const MASK_HAS_OTHER  = 0x0004;  // boolean
  const MASK_GROUPED    = 0x0008;  // boolean

  private int $bits = 0;

  public function __construct($bits=0) {
    $this->bits = $bits;
  }

  public function get_bits(): int 
  {
    return $this->bits;
  }

  private function _buttle($mask,$value,$on=1) : ?bool
  {
    // on=1 : attribute is set when bit is on
    // on=0 : attribute is set when bit is off
    if( $value === null ) {
      // this is the getter
      $value = ($this->bits & $mask) == $mask;
      return $on ? $value : !$value;
    }
    // this is the setter
    $value = $on ? $value : !$value;
    if($value) { $this->bits |=  $mask; }
    else       { $this->bits &= ~$mask; }
    return null;
  }

  public function align_right(?bool $value=null) : ?bool
  {
    return $this->_buttle(self::MASK_LEFT_RIGHT,$value,1);
  }

  public function align_left(?bool $value=null) : ?bool
  {
    return $this->_buttle(self::MASK_LEFT_RIGHT,$value,0);
  }

  public function orient_column(?bool $value=null) : ?bool
  {
    return $this->_buttle(self::MASK_ROW_COL,$value,1);
  }

  public function orient_row(?bool $value=null) : ?bool
  {
    return $this->_buttle(self::MASK_ROW_COL,$value,0);
  }

  public function has_other(?bool $value=null) : ?bool
  {
    return $this->_buttle(self::MASK_HAS_OTHER,$value);
  }

  public function grouped(?bool $value=null) : ?bool
  {
    return $this->_buttle(self::MASK_GROUPED,$value);
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
