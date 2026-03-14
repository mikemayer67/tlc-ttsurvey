<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/users.php'));

/**
 * Creates a sortable key for the specified full name.
 *   Accounts for one or more middle name and suffices.
 * @param string $fullname 
 * @return string 
 */
function surname_sort_key(string $fullname) : string {
  // written by ChatGPT

  $parts = preg_split('/\s+/', trim($fullname));

  // Known suffixes (period optional)
  $suffixes = ['jr', 'sr', 'ii', 'iii', 'iv', 'esq'];

  $last = array_pop($parts);
  $suffix = null;
  $last_clean = rtrim(strtolower($last), '.');

  // Detect suffix and pull real surname instead
  if (in_array($last_clean, $suffixes)) {
    $suffix = $last_clean;        // normalized, period removed
    $last   = array_pop($parts);  // real surname
  }

  // Create lastname-first key
  $key = strtolower($last) . ' ' . strtolower(implode(' ', $parts));

  // Re-attach suffix only as a *tiebreak segment*
  if ($suffix !== null) {
    $key .= ' ~' . $suffix;   // '~' sorts after letters → ideal low-priority key
  }

  return $key;
}

