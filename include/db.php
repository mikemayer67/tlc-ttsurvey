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
  //   If the query fails, there are two possible responses, based on the $failable parameter:
  //      true: a value of false is returned to indicate failure
  //     false: the app terminates immediately via the internal_error function. 
  public function query($sql,$failable=false)
  {
    $result = $this->db->query($sql);
    if( ! $result ) 
    { 
      if($failable) { return null; }

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
//   There is an implicit assumption inthe current design of the ttsurvey app
//     that the survey is an annual event and can be uniquely identified by 
//     year.  (There is an enhancement issue on github to consider moving away
//     from this assumption... but that's not implemented.)
//
//   The survey database can contain data from multiple survey years.  Each of
//   these d
//////////////////////////////

// The active year is the one for which the survey is in the active state.
//   Tha
function active_survey_year(

