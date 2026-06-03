<?php
namespace tlc\tts;

use mysqli;
use mysqli_stmt;
use mysqli_sql_exception;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/db.php');

class MySQLPreparedStatement
{
  protected string $query;
  protected string $types;
  protected int    $n_params;

  protected mysqli      $conn;
  protected mysqli_stmt $stmt;

  /**
   * MySQLPreparedStatement constructor
   * @param string $query Prepared statement query string (with ? pararameter placeholders)
   * @param string $types Prepared statement parameter types
   * @return void 
   */
  protected function __construct(string $query, string $types)
  {
    $this->n_params = substr_count($query,"?");
    if( strlen($types) !== $this->n_params) {
      internal_error("Mismatch between prepared statement and types length",2);
    }

    $this->query = $query;
    $this->types = $types;
    $this->conn  = MySQLConnection();
    $this->stmt = $this->conn->prepare($query);
  }

  /**
   * Binds and executes the prepared statement with the supplied parameters
   * @param array $params Prepared statement parameter values
   * @return void
   */
  protected function _bind_and_exec(...$params)
  {
    if( count($params) !== $this->n_params) {
      internal_error("Mismatch between prepared statement and parameter count",2);
    }
    try {
      $this->stmt->bind_param($this->types, ...$params);
      $this->stmt->execute();
    } catch(mysqli_sql_exception $e) {
      internal_error($e->getMessage(),2);
    }
  }
}


