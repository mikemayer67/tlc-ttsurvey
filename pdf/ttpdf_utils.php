<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('pdf/ttpdf.php'));

/**
 * Computes the actual line height used by TTPDF given current font
 * @param TTPDF $ttpdf 
 * @return float 
 */
function ttpdf_line_height(TTPDF $ttpdf) : float
{
  return $ttpdf->getFontSize() * $ttpdf->getCellHeightRatio();
}

/**
 * Truncates a text string to fit in the available width given current font
 * @param TTPDF $ttpdf 
 * @param string $text 
 * @param float $w 
 * @return string 
 */
function ttpdf_truncate_text(TTPDF $ttpdf, string $text, float $w) : string
{
  $wt = $ttpdf->GetStringWidth($text);
  if($wt < $w) { return $text; }

  $pad = $ttpdf->GetStringWidth('...');

  $n1 = 0;
  $n2 = strlen($text);

  $keep = '';

  while($n2 > $n1 + 1) {
    $nt = intdiv($n1+$n2,2);
    $st = substr($text,0,$nt);
    $wt = $ttpdf->GetStringWidth($st);
    if($wt > $w-$pad) {
      $n2 = $nt;
    } else {
      $n1 = $nt;
      $keep = $st;
    }
  }
  return $keep . '...';
}