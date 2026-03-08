<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

/**
 * Returns a sorted array of sections that contain actual questions (not just info blocks)
 * @param array $content 
 * @return array 
 */
function summary_sections(array $content) : array
{
  $sections = [];
  foreach ($content['questions'] as $question) {
    if (strtolower($question['type'] ?? '') !== 'info') {
      $sid = $question['section'] ?? null;
      if ($sid && !array_key_exists($sid, $sections)) {
        $section = $content['sections'][$sid];
        if ($section) {
          $sections[$sid] = $section;
        }
      }
    }
  }
  $sections = array_values($sections);
  usort($sections, fn($a, $b) => $a['sequence'] <=> $b['sequence']);

  return $sections;
}