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
 *   anonid:
 *     - hash of the anonymous proxy if one has been assigned to userid
 *     - hash of the userid if no anonymous proxy has been assigned
 *   token:
 *     - used to enable use of cookies to log the user in without a password
 *     - generated by the plugin when the participant registers for the survey
 *     - stored in a cookie along with the userid if cookies are enabled
 *     - may be regenerated by the participant once they have logged in
 *
 * The tlc_anonids table contains the list of all assigned anonymous
 *   proxy ids with absolutely no linkage to the userid to which they
 *   were assigned
 **/

class User {
  private $_userid   = null;
  private $_fullname = null;
  private $_email    = null;
  private $_token    = null;
  private $_password = null;
  private $_anonid   = null;
  private $_admin    = false;

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
    $this->_anonid   = $user_data['anonid'];
    $this->_admin    = $user_data['admin'] ?? false;
  }

  public function userid()       { return $this->_userid; }
  public function fullname()     { return $this->_fullname; }
  public function email()        { return $this->_email ?? null; }
  public function access_token() { return $this->_token; }
  public function admin()        { return $this->_admin; }

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

  // Full Name
  
  public function set_fullname($fullname,&$error=0)
  {
    $error = null;
    if(!adjust_and_validate_user_input('fullname',$fullname,$error)) {
      log_warning("Cannot update full name for $this->_userid: invalid name ($fullname)");
      return false;
    }
    $old_fullname = $this->_fullname;
    $result = MySQLExecute('update tlc_tt_userids set fullname=? where userid=?','ss',$fullname,$this->_userid);
    if(!$result) { return false; }

    $this->_fullname = $fullname;

    $email = $this->email();
    if($email) {
      require_once app_file('include/sendmail.php');
      sendmail_profile($email, $this->userid(), 'name', $old_fullname, $fullname);
    }
    return true;
  }

  // Email

  public function set_email($email,&$error=0)
  {
    $error = null;
    if(!adjust_and_validate_user_input('email',$email,$error)) {
      log_warning("Cannot update email for $this->_userid: invalid email ($email)");
      return false;
    }
    $old_email = $this->_email;
    if($email) {
      $result = MySQLExecute('update tlc_tt_userids set email=? where userid=?','ss',$email,$this->_userid);
    } else {
      $result = MySQLExecute('update tlc_tt_userids set email=NULL where userid=?','s',$this->_userid);
    }
    if(!$result) { return false; }

    $this->_email = $fullname;

    if($email) {
      log_info("Sent updated email address to new address: $email");
      require_once app_file('include/sendmail.php');
      sendmail_profile($email, $this->userid(), 'email', $old_email, $email);
    }
    if($old_email) {
      log_info("Sent updated email address to old address: $old_email");
      require_once app_file('include/sendmail.php');
      sendmail_profile($old_email, $this->userid(), 'email', $old_email, $email);
    }
    return true;
  }

  public function clear_email() { return $this->set_email(null); }

  

  // Password

  public function validate_password($password)
  {
    return password_verify($password,$this->_password);
  }

  public function get_password_reset_token()
  {
    // Only one active reset request at a time
    MySQLExecute("delete from tlc_tt_reset_tokens where userid=?",'s',$this->_userid);
    $token = gen_token(10);
    log_dev("get_password_reset_token: token = $token");
    $expires = time() + PW_RESET_TIMEOUT;
    log_dev("get_password_reset_expires: expires = $expires");
    $expires = gmdate('Y-m-d H:i:s', $expires);
    log_dev("get_password_reset_expires: expires = $expires");
    $r = MySQLExecute("insert into tlc_tt_reset_tokens values (?,'$token','$expires')",'s',$this->_userid);
    log_dev("get_password_reset_expires: result = ".print_r($r,true));

    return $r ? $token : null;
  }

  public function update_password($token,$password,&$error=null)
  {
    $error = null;
    $sql = "select token,expires from tlc_tt_user_reset_tokens where userid=?";
    $result = MySQLSelectRow($sql,'s', $this->_userid);
    log_dev("update_password:: current_reset: ".print_r($result,true));
    if(!$result) {
      $error = "No current password reset request";
      return false;
    }
    // You only get one chance per reset request
    MySQLExecute("delete from tlc_tt_reset_tokens where userid=?",'s',$this->_userid);
    if( $token !== $result['token'] ) {
      $error = "Invalid reset request";
      return false;
    }
    $expires = strtotime($result['expires'].' UTC');
    $now = time();
    log_dev("update_password:: expires:$expires vs now=$now  diff=".($expires-$now));
    if($now > $expires) {
      $error = "Password reset request has expired";
      return false;
    }
    log_dev("update_password:: ok to reset");

    return $this->set_password($password,$error);
  }

  public function set_password($password,&$error=0)
  {
    $error = null;
    if(!adjust_and_validate_user_input('password',$password) ) {
      log_info("Cannot update password for $this->_userid: invalid password");
      $error = 'invalid password';
      return false;
    }
    $password = password_hash($password,PASSWORD_DEFAULT);

    $result = MySQLExecute('update tlc_tt_userids set password=? where userid=?', 'ss', $password, $this->_userid);
    if(!$result) { return false; }
    $this->_password = $password;

    $email = $this->email();
    if($email) {
      require_once app_file('include/sendmail.php');
      sendmail_profile($email, $this->userid(), 'password', '(undisclosed)', '(undisclosed)');
    }
    return true;
  }

  // Access Token

  public function validate_access_token($token)
  {
    return $token === $this->_token;
  }

  public function regenerate_access_token()
  {
    $new_token = gen_token();
    $result = MySQLExecute('update tlc_tt_userids set token=? where userid=?', 'ss', $new_token, $this->_userid);
    if(!$result) { return false; }
    $this->_token = $new_token;
    return $new_token;
  }

  // Admin 

  public function make_admin()
  {
    $result = MySQLExecute("update tlc_tt_userids set admin=1 where userid=?",'s',$this->_userid);
    if(!$result) { return false; }
    log_warning("Making ".$this->_userid." an admin");
    $this->_admin = true;
    return true;
  }

  public function revoke_admin()
  {
    $result = MySQLExecute("update tlc_tt_userids set admin=0 where userid=?",'s',$this->_userid);
    if(!$result) { return false; }
    log_warning("Removing ".$this->_userid." an admin");
    $this->_admin = false;
    return true;
  }

  // Anonymous Proxy
  
  public function anonid()
  {
    $r = password_verify( $this->_userid, $this->_anonid );
    if( !$r ) {
      // try to find the existing anonymous proxy id
      $anonids = MySQLSelectValues('select * from tlc_tt_anonids');
      foreach( $anonids as $anonid ) {
        if( password_verify($anonid,$this->_anonid) ) { return $anonid; }
      }
      log_warning("Failed to locate anonid for $this->_userid");
    }

    // Either the userid was never associated with an anonymous proxy or
    //   We've somehow managed to lose that anonymous proxy id... 
    // Either way, we need to generate a new one
    //
    $anonid = 'anon_' . strtolower(gen_token(10));
    $result = MySQLExecute('insert into tlc_tt_anonids values (?)','s',$anonid);
    if(!$result) { internal_error("Failed to insert $anonid into tlc_tt_anonids"); }
    $anonid_hash = password_hash($anonid,PASSWORD_DEFAULT);
    $result = MySQLExecute('update tlc_tt_userids set anonid=? where userid=?','ss',$anonid_hash,$this->_userid);
    if(!$result) { internal_error("Failed to add anonid to $this->_userid in tlc_tt_userids"); }
    $this->_anonid = $anonid_hash;
    return $anonid;
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

  $token    = gen_token();
  $password = password_hash($password,PASSWORD_DEFAULT);
  $anonid   = password_hash($userid,PASSWORD_DEFAULT);

  if($email) {
    $r = MySQLExecute(
      "insert into tlc_tt_userids (userid,fullname,email,token,password,anonid,admin) values (?,?,?,?,?,?,0)",
      "ssssss",
      $userid,$fullname,$email,$token,$password,$anonid
    );
  } else {
    $r = MySQLExecute(
      "insert into tlc_tt_userids (userid,fullname,token,password,anonid,admin) values (?,?,?,?,?,0)",
      "sssss",
      $userid,$fullname,$token,$password,$anonid
    );
  }
  return $r;
}

function gen_token($token_length=25)
{
  $access_token = '';
  $token_pool = '123456789123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  for($i=0; $i<$token_length; $i++) {
    $index = rand(0,strlen($token_pool)-1);
    $access_token .= $token_pool[$index];
  }
  return $access_token;
}

function validate_user_password($userid,$password)
{
  $user = User::from_userid($userid);
  if(!$user) {
    log_info("Failed to validate password for $userid (invalid userid)");
    return false;
  }
  if(!$user->validate_password($password)) {
    log_info("Failed to validate password for $userid (invalid password)");
    return false;
  }
  return true;
}

function validate_user_access_token($userid,$token)
{
  $user = User::from_userid($userid);
  if(!$user) { return false; }
  return $user->validate_access_token($token);
}
