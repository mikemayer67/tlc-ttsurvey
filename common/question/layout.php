<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

enum QuestionLayout : string
{
  case ColumnLeft    = "LCOL";
  case ColumnRight   = "RCOL";
  case Row           = "ROW";
  case CheckboxLeft  = "LEFT";
  case CheckboxRight = "RIGHT";
  case None          = "";

  public function isColumn() : bool
  {
    return $this === self::ColumnLeft || $this === self::ColumnRight;
  }

  public function isRight() : bool
  {
    return $this === self::ColumnRight || $this === self::CheckboxRight;
  }
};