<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/logger.php');

////////////////////////////////////////////////////////////////////////////////
// The MySQLConnnection function creates a single connetion the mysql server.
//   The authentication uses the following parameters initialized from the
//   package config (.ini) file
//
//   tlc-ttsurvey.ini file:
//      HOST     - mysql_host
//      USERNAME - mysql_username
//      PASSWORD - mysql_password
//      SCHEMA   - mysql_host
//
//   Additionally, t sets the charset based on mysql.charset if specified
//   or 'utf8' if not specified.
//
// Note that as logger requires settings from the database to function, 
//   we cannot use any of its logging functions from with MySQLConnection,
//   we must used the native error_log function instead (which will write
//   to the generic PHP error log and not the app specific log).
//   Similarly, we cannot use internal_error as that uses log_error.
//
////////////////////////////////////////////////////////////////////////////////

function MySQLConnection() 
{
  static $conn;
  if(!$conn) {
    try {
      $config = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true);
      $username = $config['mysql_username'];
      $password = $config['mysql_password'];
      $schema   = $config['mysql_schema'];
      $host     = $config['mysql_host'];
      $charset  = $config['mysql_charset'] ?? 'utf8mb4';

      mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
      $conn = new \mysqli($host, $username, $password, $schema);
    } 
    catch(\mysqli_sql_exception $e) {
      error_log(sprintf("mysqli(%s, %s, %s, %s)",$host,$username,$password,$schema),0);
      die();
    }
    if( ! $conn->set_charset($charset) ) 
    { 
      // internal_error is ok now as we have a database connection
      internal_error('Failed to set charset to '.$charset);
    }
  }
  return $conn;
}

function MySQLBeginTransaction() { MySQLConnection()->begin_transaction(); }
function MySQLRollback()         { MySQLConnection()->rollback();          }
function MySQLCommit()           { MySQLConnection()->commit();            }

function MySQLExecute($query,$types=null,...$params)
{
  if(preg_match("/^\s*select/i",$query)) {
    internal_error("Use MySQLSelect for select queries");
  }

  $conn = MySQLConnection();

  try {
    if($types) {
      $stmt = $conn->prepare($query);
      $stmt->bind_param($types, ...$params);
      if( $stmt->execute() ) {
        return $stmt->affected_rows;
      }
    } else {
      if($conn->query($query)) {
        return $conn->affected_rows;
      }
    }
  }
  catch(\mysqli_sql_exception $e) {
    log_error($e->getMessage());
  }

  log_warning("Failed:: MySQLExecute($query,$types,".log_array($params).")");
  return false;
}

function MySQLInsertID()
{
  // this must be called immediately after the insert query that generated the new 
  $conn = MySQLConnection();
  return $conn->insert_id;
}

function MySQLSelect($all,$mode,$query,$types=null,$params=[])
{
  if(! preg_match("/^\s*select/i",$query)) {
    internal_error("Use MySQLSelect for non-select queries");
  }

  $conn = MySQLConnection();

  if($types) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    if( $stmt->execute() ) {
      $result = $stmt->get_result();
    }
  } else {
    $result = $conn->query($query);
  }

  if($result) {
    if($all) {
      return $result->fetch_all($mode);
    } else {
      return $result->fetch_array($mode);
    }
  }
  log_dev("MySQLSelect($all,$mode,$query,$types,".log_array($params).")");
  log_dev("MySQLSelect: failed");
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

// return first value in first row
function MySQLSelectValue($query,$types=null,...$params)
{
  $row = MySQLSelectArray($query,$types,...$params);
  return $row[0] ?? null;
}
// return first value in all rows 
function MySQLSelectValues($query,$types=null,...$params)
{
  $rows = MySQLSelectArrays($query,$types,...$params);
  $values = array();
  foreach($rows as $row) { $values[] = $row[0]; }
  return $values;
}

