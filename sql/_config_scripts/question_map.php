<?php

if(!defined('IN_MIGRATION')) { 
  throw new Exception(
    __FILE__ . " cannot be run directly.\n".
    "It must be included by configure_database.php"
  );
}

/**
 * Populates the v1.1.0 structure map from the v1.0.0 question map and question grouping atttributes
 * @param PDO $pdo 
 * @return void 
 */
function restructure_question_map(PDO $pdo)
{

}

?>