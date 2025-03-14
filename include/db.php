<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/const.php');
require_once app_file('include/logger.php');


////////////////////////////////////////////////////////////////////////////////
// The MySQLConnnection function creates a single connetion the mysql server.
//   The authentication uses the following constants initialized from the
//   tlc-ttsurvey.ini file:
//      MYSQL_HOST     - mysql.host
//      MYSQL_USERID   - mysql.userid
//      MYSQL_PASSWORD - mysql.password
//      MYSQL_SCHEMA   - mysql.host
//
//   Additionally, sets the charset based on mysql.charset
//
////////////////////////////////////////////////////////////////////////////////

function MySQLConnection() 
{
  static $conn;
  if(!$conn) {
    try {
      mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
      $conn = new \mysqli(MYSQL_HOST, MYSQL_USERID, MYSQL_PASSWORD, MYSQL_SCHEMA);
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
    if( ! $conn->set_charset(MYSQL_CHARSET) ) 
    { 
      internal_error('Failed to set charset to '.MYSQL_CHARSET);
    }
  }
  return $conn;
}

function MySQLExecute($query,$types=null,...$params)
{
  log_dev("MySQLExecute($query,$types,".print_r($params,true).")");

  if(preg_match("/^\s*select/i",$query)) {
    internal_error("Use MySQLExecute for non-select queries");
  }

  $conn = MySQLConnection();

  if($types) {
    log_dev("MySQLExecute:: prepared statement");
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    if( $stmt->execute() ) {
      log_dev("MySQLExecute:: executed successfully");
      return $stmt->affected_rows;
    }
  } else {
    log_dev("MySQLExecute:: direct query");
    if($conn->query($query)) {
      log_dev("MySQLExecute:: success");
      return $conn->affected_rows;
    }
  }
  log_dev("MySQLExecute: failed");
  return false;
}

function MySQLSelect($all,$mode,$query,$types=null,$params=[])
{
  log_dev("MySQLSelect::");
  log_dev("   all: $all");
  log_dev("   mode: $mode");
  log_dev("   query: $query");
  log_dev("   types: $types");
  log_dev("   params: ".print_r($params,true));

  if(! preg_match("/^\s*select/i",$query)) {
    internal_error("Use MySQLSelect for non-select queries");
  }

  $conn = MySQLConnection();

  if($types) {
    log_dev("MySQLSelect:: prepared statement");
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    if( $stmt->execute() ) {
      log_dev("MySQLSelect:: executed successfully");
      $result = $stmt->get_result();
    }
  } else {
    log_dev("MySQLSelect:: direct query");
    $result = $conn->query($query);
  }

  if($result) {
    if($all) {
      log_dev("MySQLSelect return fetched rows");
      return $result->fetch_all($mode);
    } else {
      log_dev("MySQLSelect return first fetched row");
      return $result->fetch_array($mode);
    }
  }
  log_dev("MySQLSelectRows: failed");
  return false;
}

// returns all rows as associative arrays
function MySQLSelectRows($query,$types=null,...$params)
{
  return MySQLSelect(true,MYSQLI_ASSOC,$query,$types,$params);
}

// returns first row as an associative array
function MySQLSelectRow($query,$types=null,...$params)
{
  return MySQLSelect(false,MYSQLI_ASSOC,$query,$types,$params);
}

// returns all rows as indexed arrays
function MySQLSelectArrays($query,$types=null,...$params)
{
  return MySQLSelect(true,MYSQLI_NUM,$query,$types,$params);
}

// returns first row as an indexed array
function MySQLSelectArray($query,$types=null,...$params)
{
  return MySQLSelect(false,MYSQLI_NUM,$query,$types,$params);
}

