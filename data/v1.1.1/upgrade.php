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

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_view_survey_options AS
SELECT so.survey_id, so.option_id, so.option_str, so.question_id,
       so.option_id IN (
         SELECT qo.option_id
           FROM tlc_srv_question_options qo
          GROUP BY qo.option_id
         HAVING COUNT(DISTINCT qo.question_id) > 1
       ) AS multi_mapped
  FROM tlc_srv_survey_options so;
SQL ],
];
