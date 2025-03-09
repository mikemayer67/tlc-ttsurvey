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
};


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

  $db = new MySQLConnection();
  $result = $db->query("select title from tlc_tt_active_surveys where id=$id");
  $result = $result->fetch_row();
  return $result[0];
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

