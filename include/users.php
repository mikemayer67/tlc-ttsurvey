<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file("include/db.php"));
require_once(app_file("include/validation.php"));

/**
 * The survey participant information is stored in two mysql tables
 *   tlc_tt_userids: details for each survey participant
 *   tlc_tt_anonids: mapping from userids to anonymous proxy ids
 *
 * The tlc_userids table contains the following columns:
 *   id:
 *     - primary key generated by mysql 
 *     - not explicitly used with in the survey app
 *   userid:
 *     - unique ID associated with each survey participant
 *     - selected by the participant when they registered for the survey
 *     - used by the survey app to map participants to their responses
 *   fullname:
 *     - the participant's full name as it will appear on the survey
 *       summary report.
 *     - provided by the participant when they register for the survey
 *     - may be modified by the participant once they have logged in
 *   email:
 *     - the participant's email address (optional)
 *     - provided by the participant when they register for the survey
 *     - may be added/modified by the participant once they have logged in
 *     - may be removed by the participant once they have logged in
 *   password:
 *     - the participant's password for logging into the survey
 *     - provided by the participant when they register for the survey
 *     - may be modified by the participant once they have logged in
 *     - stored internally as a one-way hash of the password
 *   token:
 *     - used to enable use of cookies to log the user in without a password
 *     - generated by the plugin when the participant registers for the survey
 *     - stored in a cookie along with the userid if cookies are enabled
 *     - may be regenerated by the participant once they have logged in
 *
 * The tlc_anonids table contains the following columns:
 *   id:
 *     - primary key generated by mysql 
 *     - not explicitly used with in the survey app
 *   anonid:
 *     - similar to the userid, but is associated with anonymous responses
 *     - only generated when a participant enters their first anonymous reponse
 *       to avoid being able to associated userid/anonid based on creation order.
 *     - association between userid/anonid is never included in any logs
 *       or mysql tables other than as described below:
 *   user_hash:
 *     - linkage between userid and anonid is obfuscated through a password
 *       hash formed from the concatenation of the anonid and the userid.
 *     - matching anonid to userid requires iterating through each pair
 *       until the has is matched.
 **/

class User {
  private $_userid = null;
  private $_fullname = null;
  private $_email = null;
  private $_token = null;
  private $_password = null;
  private $_admin = false;

  private function __construct($user_data)
  {
    log_info("construct new user: ".print_r($user_data,true));
    // user_data input is expected to be an associative array
    //
    // The data in this array must have been validated/sanitized
    //   BEFORE calling this constructor.  The constructor assumes
    //   it to be valid and complete.

    $this->_userid   = $user_data['userid'];
    $this->_fullname = $user_data['fullname'];
    $this->_email    = $user_data['email'] ?? null;
    $this->_token    = $user_data['token'];
    $this->_password = $user_data['password'];
    $this->_admin    = $user_data['admin'] ?? false;
  }

  public static function from_userid($userid)
  {
    log_dev("User::from_userid($userid)");
    $r = MySQLSelectRow('select * from tlc_tt_userids where userid=?','s',$userid);
    if($r) { return new User($r); }
    else   { return false; }
  }

  public static function from_email($email)
  {
    log_dev("User::from_email($email)");
    $result = MySQLSelectRows('select * from tlc_tt_userids where email=?','s',$email);

    $users = array();
    foreach($result as $user_data) {
      $users[] = new User($user_data);
    }
    return $users;
  }
}


function create_new_user($userid,$fullname,$password,$email=null)
{
  // inputs should be validated before calling this function... but as
  //   we're about to add this to the database, we'll validate them
  //   one last time.  If there is an issue, then there is an internal
  //   error in the app... so die
  $error = '';
  if(!adjust_and_validate_user_input('userid',$userid,$error)) {
    internal_error("Error while creating new user: userid $error");
  }
  if(!adjust_and_validate_user_input('fullname',$fullname,$error)) {
    internal_error("Error while creating new user: fullname $error");
  }
  if(!adjust_and_validate_user_input('password',$password,$error)) {
    internal_error("Error while creating new user: password $error");
  }
  if(!adjust_and_validate_user_input('email',$email,$error)) {
    internal_error("Error while creating new user: email $error");
  }

  $token    = gen_access_token();
  $password = password_hash($password,PASSWORD_DEFAULT);

  if($email) {
    $r = MySQLExecute(
      "insert into tlc_tt_userids (userid,fullname,email,token,password,admin) values (?,?,?,?,?,0)",
      "sssss",
      $userid,$fullname,$email,$token,$password
    );
  } else {
    $r = MySQLExecute(
      "insert into tlc_tt_userids (userid,fullname,token,password,admin) values (?,?,?,?,0)",
      "ssss",
      $userid,$fullname,$token,$password
    );
  }
  return $r;
}


function gen_access_token($token_length=25)
{
  $access_token = '';
  $token_pool = '123456789123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  for($i=0; $i<$token_length; $i++) {
    $index = rand(0,strlen($token_pool)-1);
    $access_token .= $token_pool[$index];
  }
  return $access_token;
}

  

