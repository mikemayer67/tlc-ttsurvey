<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

use \TCPDF;

/**
 * Computes the actual line height used by TCPDF given current font
 * @param TCPDF $tcpdf 
 * @return float 
 */
function tcpdf_line_height(TCPDF $tcpdf) : float
{
  return $tcpdf->getFontSize() * $tcpdf->getCellHeightRatio();
}

/**
 * Truncates a text string to fit in the available width given current font
 * @param TCPDF $tcpdf 
 * @param string $text 
 * @param float $w 
 * @return string 
 */
function tcpdf_truncate_text(TCPDF $tcpdf, string $text, float $w) : string
{
  $wt = $tcpdf->GetStringWidth($text);
  if($wt < $w) { return $text; }

  $pad = $tcpdf->GetStringWidth('...');

  $n1 = 0;
  $n2 = strlen($text);

  $keep = '';

  while($n2 > $n1 + 1) {
    $nt = intdiv($n1+$n2,2);
    $st = substr($text,0,$nt);
    $wt = $tcpdf->GetStringWidth($st);
    if($wt > $w-$pad) {
      $n2 = $nt;
    } else {
      $n1 = $nt;
      $keep = $st;
    }
  }
  return $keep . '...';
}