<?php

if(!defined('IN_MIGRATION')) { 
  throw new Exception(
    __FILE__ . " cannot be run directly.\n".
    "It must be included by configure_database.php"
  );
}

// we're just going to return a single array of sql commands
return [ 
  
  // Add new question_id table to survey options table and set
  //   a foreign key from this new column to the questions table

  [ __LINE__, <<<SQL
ALTER TABLE tlc_srv_survey_options 
  ADD COLUMN question_id SMALLINT(5) UNSIGNED NULL DEFAULT NULL AFTER option_str
SQL ], 

  [ __LINE__, <<<SQL
ALTER TABLE tlc_srv_survey_options 
  ADD FOREIGN KEY (question_id,survey_id) 
      REFERENCES tlc_srv_questions (question_id,survey_id)
      ON UPDATE RESTRICT
      ON DELETE CASCADE
SQL ], 

];
