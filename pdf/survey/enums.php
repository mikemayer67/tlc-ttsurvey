<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('pdf/pdf_boxes.php'));

enum SurveyJustification : string {
  case LEFT = 'LEFT';
  case RIGHT = 'RIGHT';

  public static function fromInput(string $value): self
  {
    try {
      return self::from(strtoupper(trim($value)));
    } 
    catch (\ValueError $e) 
    {
      throw new \InvalidArgumentException(
        "Invalid justification: '{$value}'",
        previous: $e
      );
    }
  }
}

enum OptionShape : string {
  case RADIO = 'RADIO';
  case CHECKBOX = 'CHECKBOX';

  public static function fromInput(string $value): self
  {
    try {
      return self::from(strtoupper(trim($value)));
    } 
    catch (\ValueError $e) 
    {
      throw new \InvalidArgumentException(
        "Invalid shape: '{$value}'",
        previous: $e
      );
    }
  }
}