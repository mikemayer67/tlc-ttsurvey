<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

enum QuestionType : string
{
  case Info         = 'INFO';  
  case Bool         = 'BOOL';
  case SelectOne    = 'SELECT_ONE';
  case SelectMulti  = 'SELECT_MULTI';
  case FreeText     = 'FREETEXT';

  /**
   * Returns a label suitable for using in the survey admin dashboard
   * @return string 
   */
  public function label() : string
  {
    return match($this) {
      self::Info        => 'Info Block',
      self::Bool        => 'Simple Checkbox',
      self::SelectOne   => 'Single Selection',
      self::SelectMulti => 'Multiple Selection',
      self::FreeText    => 'Free Text'
    };
  }

  /**
   * Returns all question type labels as an associative array
   * @return array<string,string> Maps question type to question type label
   */
  public static function labels() : array
  {
    $rval = [];
    foreach(self::cases() as $case) {
      $rval[$case->value] = $case->label();
    }
    return $rval;
  }

  public function isInfo() : bool
  {
    return $this === self::Info;
  }

  public function isSelect() : bool 
  {
    return $this === self::SelectOne || $this === self::SelectMulti;
  }
};
