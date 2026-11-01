<?php
namespace tlc\tts;

use mysqli;
use mysqli_stmt;
use mysqli_sql_exception;

use Closure;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/logger.php');

define('MYSQL_FK_CONSTRAINT_VIOLATION',1452);

/**
 * Class for tracking the singleton mysqli connection and its current transaction state
 * @package tlc\tts
 */
#[ExcludeFromLogTrace]
class MySQLConnection
{
  private static ?self $instance = null;
  private mysqli $conn;

  private bool $inTransaction = false;

  /**
   * Constructs a (singleton) MysQLConnection instance baesd on the connection
   *   credentials in the config .ini file. tlc-ttsurvey.ini file:
   *      HOST     - mysql_host
   *      USERNAME - mysql_username
   *      PASSWORD - mysql_password
   *      SCHEMA   - mysql_schema
   *      CHARSET  - mysql_charset (utf8mb4 if not specified)
   * 
   * @note On failure, kills the script with die().
   */
  private function __construct() 
  {
    try {
      $config   = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true);
      $username = $config['mysql_username'];
      $password = $config['mysql_password'];
      $schema   = $config['mysql_schema'];
      $host     = $config['mysql_host'];
      $charset  = $config['mysql_charset'] ?? 'utf8mb4';

      mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
      $this->conn = new mysqli($host, $username, $password, $schema);
    } 
    catch(mysqli_sql_exception $e) {
      // Note that as logger requires settings from the database to function, 
      //   we cannot use any of its logging functions here.
      error_log(sprintf("mysqli(%s, %s, %s, %s)",$host,$username,$password,$schema));
      die();
    }
    if( ! $this->conn->set_charset($charset) ) { 
      // internal_error is ok now as we have a database connection
      internal_error('Failed to set charset to '.$charset);
    }
  }

  /**
   * Returns the singleton instance of MySQLConnection
   */
  public static function instance() : self 
  {
    return self::$instance ??= new self();
  }

  /**
   * Returns a prepared statement for the specified query string
   * @param string $query 
   * @return mysqli_stmt 
   * @throws mysqli_sql_exception
   */
  public function prepare(string $query) : mysqli_stmt
  {
    return $this->conn->prepare($query);
  }

  /**
   * Begins and tracks status of a MySQL transaction
   * @return void
   */
  public function beginTransaction() {
    if($this->inTransaction) { internal_error("Attempting to nest transactions"); }
    $this->conn->begin_transaction();
    $this->inTransaction = true;
  }

  /**
   * Ends a MySQL transaction by committing it to database
   * @return void 
   */
  public function commit() {
    if(!$this->inTransaction) { internal_error("Attempting to commit outside a transaction"); }
    $this->conn->commit();
    $this->inTransaction = false;
  }

  /**
   * Ends a MySQL transaction by rolling back all changes without committing them to the database
   * @return void 
   */
  public function rollback() {
    if(!$this->inTransaction) { internal_error("Attempting to rollback outside a transaction"); }
    $this->conn->rollback();
    $this->inTransaction = false;
  }

  /**
   * Ends an open MySQL transaction by rolling back all changes without committing them to the database.
   *   Does nothing if there is no open transaction.
   * @return void 
   */
  public function rollback_safe() {
    if($this->inTransaction) {
      $this->conn->rollback();
      $this->inTransaction = false;
    }
  }

  /**
   * Returns whether or not there is currently an open MySQL transaction
   * @return bool 
   */
  public function inTransaction() : bool {
    return $this->inTransaction;
  }

  /**
   * Returns the auto-increment value from the last query
   * @return int
   * @note Must be called immediately after the INSERT query to get the correct ID
   */
  public function lastInsertID() : int {
    return (int) $this->conn->insert_id;
  }
}


/**
 * Initiates a mysqli transaction
 * @return void 
 */
#[ExcludeFromLogTrace]
function MySQLBeginTransaction() { MySQLConnection::instance()->beginTransaction(); }

/**
 * Rolls back a mysqli transaction
 * @param bool $safe only perform rollback if inside a transaction
 * @return void 
 */
#[ExcludeFromLogTrace]
function MySQLRollback(bool $safe=false) { 
  if($safe) { MySQLConnection::instance()->rollback_safe(); }
  else      { MySQLConnection::instance()->rollback();      }
}

/**
 * Commits a mysqli transaction
 * @return void 
 */
#[ExcludeFromLogTrace]
function MySQLCommit() { MySQLConnection::instance()->commit(); }

/**
 * Returns the auto-increment value from the last query
 * @return int
 * @note Must be called immediately after the INSERT query to get the correct ID
 */
#[ExcludeFromLogTrace]
function MySQLInsertID() : int { return MySQLConnection::instance()->lastInsertID(); }


#[ExcludeFromLogTrace]
class MySQLPreparedStatement
{
  protected string $query;
  protected string $types;
  protected int    $n_params;

  protected MySQLConnection $conn;
  protected mysqli_stmt $stmt;

  /**
   * @var null|Closure(mysqli_sql_exception,array):void
   */
  protected ?Closure $onException = null;

  /**
   * MySQLPreparedStatement constructor
   * @param string $query Prepared statement query string (with ? pararameter placeholders)
   * @param string $types Prepared statement parameter types
   * @param null|callable(mysqli_sql_exception,array $params):void $onException
   * @return void 
   */
  protected function __construct(string $query, string $types, ?callable $onException = null)
  {
    $this->n_params = substr_count($query,"?");
    if( strlen($types) !== $this->n_params) {
      internal_error("Mismatch between prepared statement and types length");
    }

    if($onException !== null ) {
      $this->onException = Closure::fromCallable($onException);
    }

    try {
      $this->query = $query;
      $this->types = $types;
      $this->conn  = MySQLConnection::instance();
      $this->stmt = $this->conn->prepare($query);
    }
    catch(mysqli_sql_exception $e)
    {
      $handler = $this->onException;
      if($handler !== null) { $handler($e,[]); }
      else { internal_error($e->getMessage()); }
    }
  }

  /**
   * Binds and executes the prepared statement with the supplied parameters
   * @param array $params Prepared statement parameter values
   * @return bool
   */
  protected function _bind_and_exec(...$params) : bool
  {
    if( count($params) !== $this->n_params) {
      internal_error("Mismatch between prepared statement and parameter count");
    }
    try {
      if(count($params)) {
        $this->stmt->bind_param($this->types, ...$params);
      }
      $this->stmt->execute();
      return true;
    } 
    catch(mysqli_sql_exception $e) {
      $handler = $this->onException;
      if($handler) { 
        $handler($e,$params);
      } else {
        MySQLRollback(safe:true);
        internal_error($e->getMessage());
      }
      return false;
    }
  }
}

#[ExcludeFromLogTrace]
class MySQLPreparedExec extends MySQLPreparedStatement
{
  /**
   * MySQLPreparedExec constructor
   * @param string $query Prepared statement query string (with ? pararameter placeholders)
   * @param string $types Prepared statement parameter types
   * @param null|callable(mysqli_sql_exception,array $params):void $onException
   * 
   * @note This class cannot be used with SELECT queries.  Doing so
   *       triggers internal error handling
   */
  public function __construct(string $query, string $types='', ?callable $onException = null)
  {
    if (preg_match("/^\s*select/i", $query)) {
      internal_error(
        "MySQLPreparedExec does not work with SELECT queries," . 
        "Use MySQLPreparedSelect instead"
      );
    }
    parent::__construct($query,$types,$onException);

    if($onException !== null) {
      $this->onException = Closure::fromCallable($onException);
    }
  }

  /**
   * Executes the prepared statement
   * @param array $params Prepared statement parameter values
   * @return ?int Number of affected rows
   * @throws mysqli_sql_exception if not handled by $onException
   * @note This method returns null if an exception was encountered.
   *       A return value of 0 indicates that statement ran fine, but
   *       that there was no resulting change in the database.
   */
  public function run(...$params) : ?int
  {
    $result = $this->_bind_and_exec(...$params);
    if(!$result) { return null; }

    return $this->stmt->affected_rows;
  }
}

#[ExcludeFromLogTrace]
class MySQLPreparedSelect extends MySQLPreparedStatement
{
  /**
   * MySQLPreparedSelect constructor
   * @param string $query Prepared statement query string (with ? pararameter placeholders)
   * @param string $types Prepared statement parameter types
   * @return void 
   * 
   * @note This class is only to be used with SELECT queries.  Any other type
   *       of query triggers internal error handling
   */
  public function __construct(string $query, string $types='')
  {
    if (! preg_match("/^\s*select/i", $query)) {
      internal_error(
        "MySQLPreparedExec only works with SELECT queries," . 
        "Use MySQLPreparedExec instead"
      );
    }
    parent::__construct($query,$types);
  }     

  // Prevent cloning
  private function __clone() {}

  /**
   * Executes the prepared statement and returns all results as an array of associative arrays
   * @param array $params Prepared statement parameter values
   * @return array All matching row data as array of associative arrays
   */
  public function fetchAllAssoc(...$params) : array 
  {
    $this->_bind_and_exec(...$params);
    $result = $this->stmt->get_result();
    $rval = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $rval;
  }

  /**
   * Executes the prepared statement and returns the first result as an associative array
   * @param array $params Prepared statement parameter values
   * @return array First matching row as an associative array (or [])
   */
  public function fetchOneAssoc(...$params) : array 
  {
    $this->_bind_and_exec(...$params);
    $result = $this->stmt->get_result();
    $rval = $result->fetch_assoc() ?? [];
    $result->free();
    return $rval;
  }

  /**
   * Executes the prepared statement and returns all results as an array of indexed arrays
   * @param array $params Prepared statement parameter values
   * @return array All matching row data as an array of indexed arrays
   */
  public function fetchAllIndexed(...$params) : array 
  {
    $this->_bind_and_exec(...$params);
    $result = $this->stmt->get_result();
    $rval = $result->fetch_all(MYSQLI_NUM);
    $result->free();
    return $rval;
  }

  /**
   * Executes the prepared statement and returns the first result as an indexed array
   * @param array $params Prepared statement parameter values
   * @return array First matching row as an indexed array (or [])
   */
  public function fetchOneIndexed(...$params) : array 
  {
    $this->_bind_and_exec(...$params);
    $result = $this->stmt->get_result();
    $rval = $result->fetch_array(MYSQLI_NUM) ?? [];
    $result->free();
    return $rval;
  }

  /**
   * Executes the prepared statement and returns the first value from each row
   * @param array $params Prepared statement parameter values
   * @return array Column value from all matched rows (or [])
   *
   * @note This function only returns the first value from each row, even if the query
   *       selects more than a single column
   */
  public function fetchColumn(...$params) : array
  {
    $this->_bind_and_exec(...$params);
    $result = $this->stmt->get_result();
    $rval = [];
    while( ($value = $result->fetch_column(0)) !== false ) {
      $rval[] = $value;
    }
    $result->free();
    return $rval;
  }

  /**
   * Executes the prepared statement and returns the first value from the first row
   * @param array $params Prepared statement parameter values
   * @return null|int|float|string Value from query (or null)
   * 
   * @note this function only returns the first value from the row, even if the query
   *   selects more than a single column
  */
  public function fetchValue(...$params) : null|int|float|string
  {
    $this->_bind_and_exec(...$params);
    $result = $this->stmt->get_result();
    $rval = $result->fetch_column(0);
    $result->free();
    return $rval !== false ? $rval : null;
  }
}

/**
 * Execute a non-SELECT MySQL query and returns number of affected rows
 * @param string $query Prepared statement query string (with ? pararameter placeholders)
 * @param string $types Prepared statement parameter types (default='')
 * @param array $params Prepared statement parameter values
 * @return int Number of affected rows (null on exception)
 *
 * @note This function will rollback the transaction (if open) and invoke internal_error 
 *       if the database query raises an exception.  Use either MySQLPreparedExec or 
 *       MySQLExecuteWithExceptionHandler if you need to handle the exception without dying.
 */
#[ExcludeFromLogTrace]
function MySQLExecute(string $query,string $types='', ...$params) : int
{
  $stmt = new MySQLPreparedExec($query,$types);
  return $stmt->run(...$params);
}

/**
 * Execute a non-SELECT MySQL query and returns number of affected rows or handles exception
 * @param string $query Prepared statement query string (with ? pararameter placeholders)
 * @param callable(mysqli_sql_exception, array):void $onException
 * @param string $types Prepared statement parameter types (default='')
 * @param mixed ...$params Prepared statement parameter values
 * @return null|int Number of affected rows (null on exception)
 *
 * @note This function will invoke the provided exception handler and return null if
 *       an exception is raised during the execution of the MySQL statement.
 */
#[ExcludeFromLogTrace]
function MySQLExecuteWithExceptionHandler(string $query, callable $onException, string $types='', ...$params) : ?int
{
  $stmt = new MySQLPreparedExec($query,$types,onException:$onException);
  return $stmt->run(...$params);
}

/**
 * Executes a SELECT query and returns all results as an array of associative arrays
 * @param string $query Prepared statement query string (with ? pararameter placeholders)
 * @param string $types Prepared statement parameter types (default='')
 * @param array $params Prepared statement parameter values
 * @return array All matching row data as array of associative arrays
 */
#[ExcludeFromLogTrace]
function MySQLFetchAllAssoc(string $query,string $types='',...$params) : array
{
  $stmt = new MySQLPreparedSelect($query,$types);
  return $stmt->fetchAllAssoc(...$params);
}

/**
 * Executes a SELECT query and returns the first result as an associative array
 * @param string $query Prepared statement query string (with ? pararameter placeholders)
 * @param null|string $types Prepared statement parameter types
 * @param array $params Prepared statement parameter values
 * @return array First matching row as an associative array (or [])
 */
#[ExcludeFromLogTrace]
function MySQLFetchOneAssoc(string $query,?string $types=null,...$params) : array
{
  $stmt = new MySQLPreparedSelect($query,$types);
  return $stmt->fetchOneAssoc(...$params);
}

/**
 * Executes a SELECT query and returns all results as an array of indexed arrays
 * @param string $query Prepared statement query string (with ? pararameter placeholders)
 * @param string $types Prepared statement parameter types (default='')
 * @param array $params Prepared statement parameter values
 * @return array All matching row data as array of indexed arrays
 */
#[ExcludeFromLogTrace]
function MySQLFetchAllIndexed(string $query,string $types='',...$params) : array
{
  $stmt = new MySQLPreparedSelect($query,$types);
  return $stmt->fetchAllIndexed(...$params);
}

/**
 * Executes a SELECT query and returns the first result as an indexed array
 * @param string $query Prepared statement query string (with ? pararameter placeholders)
 * @param string $types Prepared statement parameter types (default='')
 * @param array $params Prepared statement parameter values
 * @return array First matching row as an indexed array (or [])
 */
#[ExcludeFromLogTrace]
function MySQLFetchOneIndexed(string $query,string $types='',...$params) : array
{
  $stmt = new MySQLPreparedSelect($query,$types);
  return $stmt->fetchOneAssoc(...$params);
}

/**
 * Executes a SELECT query and returns the first value in each row
 * @param string $query Prepared statement query string (with ? pararameter placeholders)
 * @param string $types Prepared statement parameter types (default='')
 * @param array $params Prepared statement parameter values
 * @return array Column value from all matched rows (or [])
   *
   * @note This function only returns the first value from each row, even if the query
   *       selects more than a single column
 */
#[ExcludeFromLogTrace]
function MySQLFetchColumn(string $query,string $types='',...$params) : array
{
  $stmt = new MySQLPreparedSelect($query,$types);
  return $stmt->fetchColumn(...$params);
}

/**
 * Executes a SELECT query and returns first value from ,first row 
 * @param string $query Prepared statement query string (with ? pararameter placeholders)
 * @param string $types Prepared statement parameter types (default='')
 * @param array $params Prepared statement parameter values
 * @return null|int|float|string Value from query
 * 
 * @note this function only returns the first value from the row, even if the query
 *   selects more than a single column
 */
#[ExcludeFromLogTrace]
function MySQLFetchValue(string $query,string $types='',...$params) : null|int|float|string
{
  $stmt = new MySQLPreparedSelect($query,$types);
  return $stmt->fetchValue(...$params);
}

/**
 * Verifies that the min requred tlc_tt versioning has been implemented.
 *   If not, invoke internal_error to make it immediately obvious that 
 *   something went wrong with the upgrade.
 * @param string $required 
 * @return void 
 */
function verify_required_db_version(string $required) : void
{
  $cur_version = MySQLFetchValue( <<<SQL
    SELECT version from tlc_srv_version_history
     WHERE added in (
       SELECT max(added) from tlc_srv_version_history
     )
    SQL 
  );
  
  if(version_compare($cur_version,$required,'!=')) 
  {
    $config = parse_ini_file(APP_DIR.'/'.PKG_NAME.'.ini',true);
    $schema   = $config['mysql_schema'];
    internal_error("Need to upgrade $schema database version to $required");
  }
}