<?php

namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

require_once(app_file('include/cookiejar.php'));
require_once(app_file('include/access_tokens.php'));

// user login state

/**
 * unsets the active user authentication
 * @return void 
 */
function logout_active_user()
{
//  $userid = active_userid();
//  $token  = active_token();
//  if($userid && $token) { remove_access_token($userid, $token); }

  CookieJar::clear_active_userid();
}

/**
 * Sets up cookies/tokens for authentication for an autheticated userid
 * @param string $userid (should be previously authenticated)
 * @param bool $remember the access token 
 * @return void
 */
function start_survey_as(string $userid, bool $remember = false)
{
  $userid = strtolower($userid);
  $token = generate_access_token($userid);
  CookieJar::set_active_userid($userid, $token, $remember);
}

/**
 * Attempts to set up cookies/tokens for authentication given a userid/pasword
 *   Will fail if userid/password are invalid
 * @param string $userid 
 * @param string $token 
 * @return bool indicating success of resuming with userid and token
 */
function resume_survey_as(string $userid, string $token): bool
{
  $userid = strtolower($userid);
  if (!validate_access_token($userid, $token)) { return false; }

  $new_token = regenerate_access_token($userid, $token);
  // set remember to true as the fact that we are resuming the survey with a token
  //  we must be caching the access tokens for the current user
  CookieJar::set_active_userid($userid, $new_token, true);
  return true;
}

/**
 * Generates a new access token, replacing the old one, for the active user
 * @return void
 */
function regen_active_token()
{
  $userid = active_userid();
  $token  = active_token();
  $new_token = regenerate_access_token($userid,$token);
  CookieJar::update_active_token($new_token);
}
