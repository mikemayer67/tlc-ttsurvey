<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));

enum SurveyJustification : string {
  case LEFT = 'LEFT';
  case RIGHT = 'RIGHT';

  public static function fromInput(string $value): self
  {
    switch (strtoupper($value)) {
      case 'LEFT':  $rval = self::LEFT;  break;
      case 'RIGHT': $rval = self::RIGHT; break;
      case 'LCOL':  $rval = self::LEFT;  break;
      case 'RCOL':  $rval = self::RIGHT; break;
      case 'ROW':   $rval = self::RIGHT; break;
      default:
        throw new \InvalidArgumentException(
          "Unrecognized justification: '{$value}'"
        );
        break;
    }
    return $rval;
  }
}

enum OptionShape {
  case RADIO;
  case CHECKBOX;

  public static function fromInput(string $value) : self
  {
    switch (strtoupper($value)) {
      case 'BOOL':         $rval = self::CHECKBOX; break;
      case 'SELECT_MULTI': $rval = self::CHECKBOX; break;
      case 'SELECT_ONE':   $rval = self::RADIO;    break;
      default:
        throw new \InvalidArgumentException(
          "Unrecognized shape determinator '{$value}'"
        );
        break;
    }
    return $rval;
  }
}

enum OptionLayout {
  case ROW;
  case COLUMN;

  public static function fromInput(string $value) : self
  {
    switch(strtoupper($value)) {
      case 'ROW':  $rval = self::ROW;    break;
      case 'LCOL': $rval = self::COLUMN; break;
      case 'RCOL': $rval = self::COLUMN; break;
      default:
        throw new \InvalidArgumentException("Invalid option layout: '{$value}'");
        break;
    }
    return $rval;
  }
}