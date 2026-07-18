<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('include/question_types'));
require_once(app_file('include/question_layout'));
require_once(app_file('pdf/pdf_boxes.php'));


enum SurveyJustification : string {
  case LEFT  = 'LEFT';
  case RIGHT = 'RIGHT';

  public static function fromInput(QuestionLayout $value): self
  {
    return $value->isRight() ? self::RIGHT : self::LEFT;
  }
}

enum OptionShape {
  case RADIO;
  case CHECKBOX;

  public static function fromInput(QuestionType $value) : self
  {
    switch ($value) {
      case QuestionType::Bool:        $rval = self::CHECKBOX; break;
      case QuestionType::SelectMulti: $rval = self::CHECKBOX; break;
      case QuestionType::SelectOne:   $rval = self::RADIO;    break;
      default:
        throw new \InvalidArgumentException(
          "Unrecognized shape determinator '{$value}'"
        );
        break;
    }
    return $rval;
  }
}