<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/const.php');
require_once app_file('include/logger.php');

class MySQLConnection {
  public  $db = null;

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

      throw new Exception("Invalid SQL: $sql  [invoked at: $file:$line]",500); 
    }
    return $result;
  }

  public function escape($value)
  {
    return $this->db->real_escape_string($value);
  }
};
