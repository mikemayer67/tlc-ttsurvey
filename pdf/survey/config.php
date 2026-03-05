<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

// This file provides the common look and feel for the Survey PDF
//  Unlike tcpdf_config.php, this need only be required once needed.
//  It does not override any of the TCPDF constants.

if(!defined('K_SURVEY_FONT_BASE')) 
{
  define('K_SURVEY_FONT_BASE',     10);
  define('K_SURVEY_FONT_HSF',      1.2);
  
  define('K_SURVEY_FONT_XX_SMALL', round( K_SURVEY_FONT_BASE * pow(K_SURVEY_FONT_HSF,-3)) );
  define('K_SURVEY_FONT_X_SMALL',  round( K_SURVEY_FONT_BASE * pow(K_SURVEY_FONT_HSF,-2)) );
  define('K_SURVEY_FONT_SMALL',    round( K_SURVEY_FONT_BASE * pow(K_SURVEY_FONT_HSF,-1)) );
  define('K_SURVEY_FONT_MEDIUM',   round( K_SURVEY_FONT_BASE ) );
  define('K_SURVEY_FONT_LARGE',    round( K_SURVEY_FONT_BASE * pow(K_SURVEY_FONT_HSF, 1)) );
  define('K_SURVEY_FONT_X_LARGE',  round( K_SURVEY_FONT_BASE * pow(K_SURVEY_FONT_HSF, 2)) );
  define('K_SURVEY_FONT_XX_LARGE', round( K_SURVEY_FONT_BASE * pow(K_SURVEY_FONT_HSF, 3)) );
}