<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/const.php');
require_once app_file('include/logger.php');


////////////////////////////////////////////////////////////////////////////////
// The MySQLConnnection class creates a single connetion the mysql server.
//   The authentication uses the following constants initialized from the
//   tlc-ttsurvey.ini file:
//      MYSQL_HOST     - mysql.host
//      MYSQL_USERID   - mysql.userid
//      MYSQL_PASSWORD - mysql.password
//      MYSQL_SCHEMA   - mysql.host
//
//   Additionally, if mysql.charset is also defined in the .ini file,
//      this will be set as the charset to use in all queries
////////////////////////////////////////////////////////////////////////////////

class MySQLConnection {
  public  $db = null;  // The singleton database connection

  // The singleton is created the first time an instance of MySQLConnection
  //   is constructed.  If the connection to the mysql server fails, the app will
  //   terminate immediately via the internal_error function.
  function __construct()
  {
    if(is_null($this->db))
    {

      try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $mysqli = new \mysqli(MYSQL_HOST, MYSQL_USERID, MYSQL_PASSWORD, MYSQL_SCHEMA);
      } 
      catch(\mysqli_sql_exception $e) {
        log_dev("MySQL host: ".MYSQL_HOST);
        log_dev("MySQL userid: ".MYSQL_USERID);
        log_dev("MySQL password: ".MYSQL_PASSWORD);
        log_dev("MySQL schema: ".MYSQL_SCHEMA);
        log_dev(sprintf("mysqli(%s, %s, %s, %s)",MYSQL_HOST,MYSQL_USERID,MYSQL_PASSWORD,MYSQL_SCHEMA));
        internal_error(
          sprintf("Failed to connect to database: ".$e->getMessage())
        );
      }

      if( ! $mysqli->set_charset(MYSQL_CHARSET) ) 
      { 
        internal_error('Failed to set charset to '.MYSQL_CHARSET);
      }

      $this->db = $mysqli;
    }
  }

  // Executes the query on the mysql server and returns the result.
  public function query($sql)
  {
    $result = $this->db->query($sql);
    if( ! $result ) 
    { 
      $sql = preg_replace('/\s+/',' ',$sql);
      $sql = preg_replace('/^\s/','',$sql);
      $sql = preg_replace('/\s$/','',$sql);

      $trace = debug_backtrace();
      $file = $trace[0]["file"];
      $line = $trace[0]["line"];

      internal_error("Invalid SQL: $sql  [invoked at: $file:$line]"); 
    }
    return $result;
  }

  // Wrapper around mysqli::real_escape_string, which sanitizes
  //   an SQL query to protect against SQL injection attacks.
  //   See https://www.php.net/manual/en/mysqli.real-escape-string.php for details
  public function escape($value)
  {
    return $this->db->real_escape_string($value);
  }

  public function connection() { return $this->db; }
};

function MySQLQuery($query,$types=null,...$params)
{
  log_dev("MySQLQuery($query,$types,".print_r($params,true).")");
  $db = new MySQLConnection();
  $conn = $db->connection();

  if($types) {
    log_dev("MySQLQuery:: prepared statement");
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    if( $stmt->execute() ) {
      log_dev("MySQLQuery:: executed successfully");
      $result = $stmt->get_result();
      log_dev("MySQLQuery result: ". print_r($result,true));
      if($result === false) {
        log_dev("MySQLQuery return affected rows");
        return $stmt->affected_rows;
      } else {
        log_dev("MySQLQuery return fetched rows");
        return $result->fetch_all(MYSQLI_ASSOC);
      }
    }
  } else {
    log_dev("MySQLQuery:: direct query");
    $result = $conn->query($query);
    if($result) {
      log_dev("MySQLQuery:: success");
      return $result->fetch_all(MYSQLI_ASSOC);
    }
  }
  log_dev("MySQLQuery: failed");
  return false;
}


////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
//
// Misc. survey queries
//
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

//////////////////////////////
// Survey Status
//////////////////////////////

// returns the id of the active (not draft or closed) survey
//   terminates the app via internal_error if more than one active survey
function active_survey_id()
{
  $db = new MySQLConnection();
  $result = $db->query("select id from tlc_tt_active_surveys");
  $result = $result->fetch_all();

  $ids = array();
  foreach($result as $id) { $ids[] = $id[0]; }

  switch(count($ids)) {
  case 0: return null;    break;
  case 1: return $ids[0]; break;
  default:
    internal_error("Multiple active surveys found in the database: ".implode(', ',$ids));
    break;
  }
}

function active_survey_title()
{
  $id = active_survey_id();
  if(!isset($id)) { return "Time and Talent Survey"; }

  $result = MySQLQuery( "select title from  tlc_tt_active_surveys where id=?",'i',$id);
  return $result[0]['title'];
}

// returns the ids of all draft surveys
function draft_survey_ids()
{
  $db = new MySQLConnection();
  $result = $db->query("select id from tlc_tt_draft_surveys");
  $result = $result->fetch_all();

  $ids = array();
  foreach($result as $id) { $ids[] = $id[0]; }

  return $ids;
}

//////////////////////////////
// Userids
//////////////////////////////

function db_add_user($userid,$fullname,$email,$token,$password,$admin=false)
{
  log_dev("db_add_user($userid,$fullname,$email,$token,$password,$admin)");
  $password = password_hash($password,PASSWORD_DEFAULT);
  $admin    = $admin ? 1 : 0;

  if($email) {
    log_dev("db_add_user:: has email");
    $r = MySQLQuery(
      "insert into tlc_tt_userids (userid,fullname,email,token,password,admin) values (?,?,?,?,?,?)",
      "sssssi",
      $userid,$fullname,$email,$token,$password,$admin
    );
  } else {
    log_dev("db_add_user:: no email");
    $r = MySQLQuery(
      "insert into tlc_tt_userids (userid,fullname,token,password,admin) values (?,?,?,?,?)",
      "ssssi",
      $userid,$fullname,$token,$password,$admin
    );
  }
  log_dev("db_add_user result: ".print_r($r,true));
}

function db_get_all_from_userid($userid)
{
  log_dev("db_get_all_from_userid($userid)");
  $r = MySQLQuery("select * from tlc_tt_userids where userid=?","s",$userid);
  log_dev("db_get_all_from_userid result: ".print_r($r,true));
}

function db_drop_user($userid)
{
  log_dev("db_drop_user($userid)");
  $r = MySQLQuery("delete  from tlc_tt_userids where userid=?","s",$userid);
  log_dev("db_drop_user result: ".print_r($r,true));
}

