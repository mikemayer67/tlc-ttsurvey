<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/question_types.php'));
require_once(app_file('include/question_layout.php'));

class QuestionFlags {
  const MASK_LEFT_RIGHT = 0x0001;  // 0:LEFT  1:RIGHT
  const MASK_ROW_COL    = 0x0002;  // 0:ROW   1:COLUMN
  const MASK_HAS_OTHER  = 0x0004;  // boolean
  // 0x0008 is now OBE and available for reuse
  const MASK_IN_GROUP   = 0x0010;  // render info block in group

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

  /**
   * Getter/Setter for "render in group"
   * @param null|bool $value (null=getter, bool=setter)
   * @return null|bool setter:null, getter:bool
   */
  public function render_in_group(?bool $value=null) : ?bool
  {
    if($value === null) { // getter
      return ($this->bits & self::MASK_IN_GROUP) === self::MASK_IN_GROUP;
    } 
    elseif($value) { // set align right
      $this->bits |= self::MASK_IN_GROUP;
    } 
    else { // clear align right
      $this->bits &= ~self::MASK_IN_GROUP;
    }
    return null;
  }

  /**
   * Getter/Setter for layout
   * @param QuestionType $question_type 
   * @param null|QuestionLayout $value (null=getter, QuestionLayout=setter)
   * @return null|QuestionLayout setter:null, getter:QuestionLayout
   */
  public function layout(QuestionType $question_type, ?QuestionLayout $value=null) : ?QuestionLayout
  {
    if( $value === null ) {
      //this is the getter
      return match($question_type) {
        QuestionType::Bool => (
          $this->align_right() ? QuestionLayout::CheckboxRight : QuestionLayout::CheckboxLeft
        ),
        QuestionType::SelectOne,
        QuestionType::SelectMulti => (
          $this->orient_row() 
          ? QuestionLayout::Row 
          : ( $this->align_right() ? QuestionLayout::ColumnRight : QuestionLayout::ColumnLeft )
        ),
        QuestionType::Info,
        QuestionType::FreeText => QuestionLayout::None
      };
    }
    // this is the setter
    $this->orient_column($value->isColumn());
    $this->align_right($value->isRight());
    return null;
  }
}
