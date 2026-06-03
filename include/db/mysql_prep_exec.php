<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once app_file('include/db.php');

class MySQLPreparedExec extends MySQLPreparedStatement
{
  /**
   * MySQLPreparedExec constructor
   * @param string $query Prepared statement query string (with ? pararameter placeholders)
   * @param string $types Prepared statement parameter types
   * @return void 
   * 
   * @note This class cannot be used with SELECT queries.  Doing so
   *       triggers internal error handling
   */
  public function __construct(string $query, string $types='')
  {
    if (preg_match("/^\s*select/i", $query)) {
      internal_error(
        "MySQLPreparedExec does not work with SELECT queries," . 
        "Use MySQLPreparedSelect instead",
        1);
    }
    parent::__construct($query,$types);
  }

  /**
   * Executes the prepared statement
   * @param array $params Prepared statement parameter values
   * @return int Number of affected rows
   */
  public function run(...$params) : int
  {
    $this->_bind_and_exec(...$params);
    return $this->stmt->affected_rows;
  }
}