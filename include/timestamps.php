<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

function recent_timestamp_string($timestamp) 
{
  $now   = time();

  // Find the offset in days where positive is in past, negative is in future.
  //   Note that his will be slightly off when dealing with DST transitions,
  //        but that is ok as this will only be used for coarse filtering.
  $delta = ($now - $timestamp)/86400;  // days:  

  // using DateTimeImmutable so that add/sub return new objects rather than mutating the DateTime
  $ts  = new \DateTimeImmutable('@'.$timestamp);
  $now = new \DateTimeImmutable('@'.$now);

  // Start with the "actual" date format, only including the year if not thie current year.
  $date_fmt = ($ts->format('Y') === $now->format('Y')) ? 'M j' : 'M j, Y';
  $date_str = $ts->format($date_fmt);

  // See if we can replace the date string with a "relative" date, e.g. today, tomorrow, Wed.
  //  (Skip this step if the date is older than 6 days or more than 2 days in the future)
  if($delta > -2 && $delta < 6) 
  {
    $ymd = 'Ymd';

    $ts_ymd = $ts->format($ymd);
    $p1d = new \DateInterval('P1D');

    $now_ymd = $now->format($ymd);

    $tomorrow  = $now->add($p1d);
    $tomorrow_ymd  = $tomorrow->format($ymd);

    $yesterday = $now->sub($p1d);
    $yesterday_ymd = $yesterday->format($ymd);

    if($ts_ymd === $now_ymd)             { $date_str = 'Today';     } 
    elseif( $ts_ymd === $tomorrow_ymd )  { $date_str = 'Tomorrow';  }
    elseif( $ts_ymd === $yesterday_ymd ) { $date_str = 'Yesterday'; }
    else 
    {
      // check the 4 days prior to yesterday (5 days before today)
      //   we don't want to go back 6 days as that brings in ambiguity with 'tomorrow'
      //   if today is Friday, then 6 days ago was Saturday... 
      $test_ts = $yesterday;
      for($i=0; $i<4; ++$i) {
        $test_ts  = $test_ts->sub($p1d);
        $test_ymd = $test_ts->format($ymd);
        if($ts_ymd === $test_ymd) 
        {
          $date_str = $test_ts->format('l');
          break;
        }
      }
    }
  }

  $time_str = $ts->format('g:ia');

  return "$time_str $date_str";
}

