<?php
namespace tlc\tts;

use mysqli_result;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/mysql_prep_stmt.php');

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
        "Use MySQLPreparedExec instead",
        1);
    }
    parent::__construct($query,$types);
  }

  /**
   * Executes the prepared statement and returns all results as an array of associative arrays
   * @param array $params Prepared statement parameter values
   * @return array All matching row data as array of associative arrays
   *
   * @note If there is a mismatch between the prepared query statement and
   *       the passed parameters, this function will trigger a call to 
   *       internal_error and never return.
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
   *
   * @note If there is a mismatch between the prepared query statement and
   *       the passed parameters, this function will trigger a call to 
   *       internal_error and never return.
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
   *
   * @note If there is a mismatch between the prepared query statement and
   *       the passed parameters, this function will trigger a call to 
   *       internal_error and never return.
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
   *
   * @note If there is a mismatch between the prepared query statement and
   *       the passed parameters, this function will trigger a call to 
   *       internal_error and never return.
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
   * @return array Values from all matched rows (or [])
   *
   * @note This function only returns the first value from each row, even if the query
   *       selects more than a single column
   * 
   * @note If there is a mismatch between the prepared query statement and
   *       the passed parameters, this function will trigger a call to 
   *       internal_error and never return.
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
   * 
   * @note If there is a mismatch between the prepared query statement and
   *       the passed parameters, this function will trigger a call to 
   *       internal_error and never return.
   */
  public function fetchValue(...$params) : null|int|float|string
  {
    $this->_bind_and_exec(...$params);
    $result = $this->stmt->get_result();
    $rval = $result->fetch_column(0);
    $result->free();
    return $rval;
  }
}

