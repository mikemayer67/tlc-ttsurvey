<?php

if(!defined('IN_MIGRATION')) { 
  throw new Exception(
    __FILE__ . " cannot be run directly.\n".
    "It must be included by configure_database.php"
  );
}

// we're just going to return a single array of sql commands
return [

// version history is used in database migration to determine what
//   needs to be constructed and/or copied between database versions.
  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_version_history (
  version VARCHAR(32) PRIMARY KEY,
  change_description VARCHAR(512) NOT NULL,
  added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
SQL ],

// Overall app settings not tied to any given survey
  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_settings (
  name  VARCHAR(24)  NOT NULL PRIMARY KEY,
  value VARCHAR(255) NOT NULL
);
SQL ],

// string length for title is enforced in the validate_survey_name function
//   in admin/js/surveys/metadata.js
  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_surveys (
  survey_id   SMALLINT UNSIGNED NOT NULL,
  parent_id   SMALLINT UNSIGNED DEFAULT NULL,
  title       VARCHAR(128) NOT NULL,
  created     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modified    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  active      DATETIME DEFAULT NULL,
  closed      DATETIME DEFAULT NULL,
  PRIMARY KEY (survey_id),
  FOREIGN KEY (parent_id) REFERENCES tlc_srv_surveys(survey_id) ON UPDATE RESTRICT ON DELETE SET NULL
);
SQL ],

// Survey questions are the whole point of the survey and come in in four flavors:
//    INFO      Not actually a question, exists to provide info to the survey participants.
//    BOOL      Yes/No type question (probably will be implemented as a checkbox)
//    OPTIONS   Multiple choice (option) questions.
//    FREETEXT  Question where the participant can provide a free form written respone
//
// This is the only table that is indexed by survey_id where the survey_id is not the
//   first column.  This is because questions transcend any given survey.
//   The question_id is used to provide continuity between surveys.  While each survey is
//   free to modify a given question, the intent is that a given question_id is
//   essentially the same question across any family of surveys.
//
// question_flags is a bitfield with the following masks/values
//   0x01 :: Alignment      on=RIGHT    off=LEFT
//   0x02 :: Orientation    on=COLUMN   off=ROW
//   0x04 :: Has Other      on=YES      off=NO
//
// string lengths for wording, other, qualifier, into, and info are enforced
//   in the Question Editor block in admin/survey_frame.php
  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_questions (
  question_id    SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL COMMENT 'Provides continuity between surveys',
  survey_id      SMALLINT UNSIGNED NOT NULL,
  wording        VARCHAR(128)      DEFAULT NULL       COMMENT 'The wording of this question shown in the survey (except for INFO)',
  question_type  ENUM('INFO','BOOL','OPTIONS','FREETEXT','SELECT_MULTI','SELECT_ONE') NOT NULL ,
  question_flags INT               NOT NULL DEFAULT 0 COMMENT 'bit0:alignment, bit1:orientation, bit2:other',
  other          VARCHAR(45)       DEFAULT NULL       COMMENT 'For OPTIONS type, label to use in the survey for the "other" input field',
  qualifier      VARCHAR(45)       DEFAULT NULL       COMMENT 'For OPTIONS/BOOL types, provide a text input field with the specified label',
  intro          VARCHAR(512)      DEFAULT NULL       COMMENT 'For noI n-INFO types, provides a intro of the question on the survey',
  info           VARCHAR(1024)     DEFAULT NULL       COMMENT 'Additional information about the question. For INFO, will appear on the form.  For all others, will appear in pop-ups.',
  PRIMARY KEY (question_id,survey_id),
  FOREIGN KEY (survey_id) REFERENCES tlc_srv_surveys(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],

// Question groups are used as a container for questions that inherently go together.  The questions
//   in a question group will be rendered in such way ss to show they "go together."  These are  
//   purely for aesthetic layout of the survey form and response summary reports.  
// Group names exist soley for the purpose of identifiying them in the survey editor in the admin 
//   dashboard.  They will never appear in the survey or summaries.
//
// string length for name is enforeced in the Section Editor block in admin/survey_frame.php
  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_question_groups (
  survey_id SMALLINT UNSIGNED NOT NULL,
  group_id  SMALLINT UNSIGNED NOT NULL,
  name      VARCHAR(128)      NOT NULL,
  PRIMARY KEY (survey_id,group_id),
  FOREIGN KEY (survey_id) REFERENCES tlc_srv_surveys(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],   

// Survey sections are the top level containers used for organizing the survey questions and
//   question groups. Sections form the basis for collapisble "tabs" and for headers
//   in survey repsonse summary reports.
//
// string lengths for name, and intro are enforeced
//   in the Section Editor block in admin/survey_frame.php
  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_sections (
  survey_id    SMALLINT UNSIGNED NOT NULL,
  section_id   SMALLINT UNSIGNED NOT NULL,
  name         VARCHAR(128)      NOT NULL     COMMENT 'Section name that will appear in the editor and on survey tabs',
  collapsible  TINYINT  UNSIGNED DEFAULT NULL COMMENT 'Whether or not the section will be rendered as collapsible',
  intro        VARCHAR(512)      DEFAULT NULL COMMENT 'Introductory text rendered at the top of the section',
  PRIMARY KEY (survey_id,section_id),
  FOREIGN KEY (survey_id)    REFERENCES tlc_srv_surveys(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],

// And now to map out the survey structure
//  sections > groups > questions

// The section content map provides the list and order of the question groups that appear in each section.
  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_section_content (
  survey_id    SMALLINT UNSIGNED NOT NULL,
  section_id   SMALLINT UNSIGNED NOT NULL,
  sequence     SMALLINT UNSIGNED NOT NULL,
  group_id     SMALLINT UNSIGNED DEFAULT NULL,
  question_id  SMALLINT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (survey_id,section_id,sequence),
  UNIQUE KEY  (survey_id,group_id,question_id),
  FOREIGN KEY (survey_id,section_id)   REFERENCES tlc_srv_sections(survey_id,section_id)      ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (survey_id,group_id)     REFERENCES tlc_srv_question_groups(survey_id,group_id) ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (question_id,survey_id)  REFERENCES tlc_srv_questions(question_id,survey_id)    ON UPDATE RESTRICT ON DELETE CASCADE,
  CHECK ((group_id IS NOT NULL AND question_id IS NULL)
     OR  (group_id IS     NULL AND question_id IS NOT NULL))
);
SQL ],

// The group content map provides the list and order of the questions that appear in each question group.
  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_group_content (
  survey_id    SMALLINT UNSIGNED NOT NULL,
  group_id     SMALLINT UNSIGNED NOT NULL,
  sequence     SMALLINT UNSIGNED NOT NULL,
  question_id  SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (survey_id,group_id,sequence),
  UNIQUE KEY  (survey_id,question_id),
  FOREIGN KEY (survey_id,group_id) REFERENCES tlc_srv_section_content(survey_id,group_id) ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (question_id,survey_id) REFERENCES tlc_srv_questions(question_id,survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],

// Options used in single and multi select questions.
//   To encourage reuse of options, they are defined to be associated with a survey
//   but are then used by questions

// @@@ TODO... enforce the string length for the option string
  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_survey_options (
  survey_id  SMALLINT UNSIGNED NOT NULL,
  option_id  SMALLINT UNSIGNED NOT NULL COMMENT 'Provides continuity between surveys',
  option_str VARCHAR(128)      NOT NULL COMMENT 'What will appear in the survey form',
  PRIMARY KEY (survey_id,option_id),
  FOREIGN KEY (survey_id) REFERENCES tlc_srv_surveys(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],

  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_question_options (
  survey_id   SMALLINT UNSIGNED NOT NULL,
  question_id SMALLINT UNSIGNED NOT NULL,
  sequence    SMALLINT UNSIGNED NOT NULL,
  option_id   SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (survey_id,question_id,sequence),
  UNIQUE  KEY (survey_id,question_id,option_id), 
  FOREIGN KEY (survey_id) REFERENCES tlc_srv_surveys(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (survey_id,option_id) REFERENCES tlc_srv_survey_options(survey_id,option_id) ON UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],

// Participant (user) management

  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_userids (
  userid   VARCHAR(24)  PRIMARY KEY,
  fullname VARCHAR(100) NOT NULL,
  email    VARCHAR(45)  DEFAULT NULL,
  password VARCHAR(64)  NOT NULL COMMENT 'hash of the password',
  admin    TINYINT      UNSIGNED NOT NULL DEFAULT 0 COMMENT 'has admin permission'
);
SQL ],

  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_access_tokens (
  userid   VARCHAR(24)  NOT NULL,
  env_id   VARCHAR(25)  NOT NULL COMMENT 'browser environment token',
  token    VARCHAR(45)  NOT NULL COMMENT 'access token',
  expires  DATETIME     NOT NULL COMMENT 'when the token expires unless renewed',
  PRIMARY KEY (userid,env_id),
  FOREIGN KEY (userid) REFERENCES tlc_srv_userids(userid) on UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],

  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_reset_tokens (
  userid    VARCHAR(24)      NOT NULL PRIMARY KEY,
  token     VARCHAR(20)      NOT NULL,
  expires   DATETIME         NOT NULL,
  FOREIGN KEY (userid) REFERENCES tlc_srv_userids(userid) ON UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],

  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_roles (
  userid    VARCHAR(24)          NOT NULL PRIMARY KEY,
  admin     TINYINT     UNSIGNED NOT NULL DEFAULT 0,
  content   TINYINT     UNSIGNED NOT NULL DEFAULT 0,
  tech      TINYINT     UNSIGNED NOT NULL DEFAULT 0,
  summary   TINYINT     UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (userid) REFERENCES tlc_srv_userids(userid) ON UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],

  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_user_status (
  userid      VARCHAR(24)          NOT NULL,
  survey_id   SMALLINT    UNSIGNED NOT NULL,
  draft       DATETIME             DEFAULT NULL,
  submitted   DATETIME             DEFAULT NULL,
  email_sent  DATETIME             DEFAULT NULL,
  sent_to     VARCHAR(45)          DEFAULT NULL,
  PRIMARY KEY (userid,survey_id),
  FOREIGN KEY (userid)    REFERENCES tlc_srv_userids(userid)    ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (survey_id) REFERENCES tlc_srv_surveys(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],

  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_reminder_emails (
  userid    VARCHAR(24) NOT NULL,
  subject   VARCHAR(32) NOT NULL,
  last_sent DATETIME    NOT NULL,
  email     VARCHAR(45) NOT NULL,
  PRIMARY KEY (userid),
  FOREIGN KEY (userid) REFERENCES tlc_srv_userids(userid) on UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],

// And now, participant responses
//   (it's nice to have a structured survey, but without responses, what's the point?)

  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_responses (
  userid      VARCHAR(24)          NOT NULL,
  survey_id   SMALLINT    UNSIGNED NOT NULL,
  question_id SMALLINT    UNSIGNED NOT NULL,
  draft       TINYINT     UNSIGNED NOT NULL     COMMENT '1=draft response, 0=submitted response',
  selected    SMALLINT    UNSIGNED DEFAULT NULL COMMENT '1/0 or select id based on question type',
  free_text   text                 DEFAULT NULL COMMENT 'reponse to free text questions',
  qualifier   text                 DEFAULT NULL COMMENT 'response qualifying information',
  other       VARCHAR(128)         DEFAULT NULL COMMENT 'user provided other-option text',
  PRIMARY KEY (userid,survey_id,question_id,draft),
  FOREIGN KEY (userid,survey_id) REFERENCES tlc_srv_user_status(userid,survey_id) ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES tlc_srv_questions(question_id) ON UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],

  [ __LINE__, <<<SQL
CREATE TABLE tlc_srv_response_options (
  userid      VARCHAR(24)          NOT NULL,
  survey_id   SMALLINT    UNSIGNED NOT NULL,
  question_id SMALLINT    UNSIGNED NOT NULL,
  draft       TINYINT     UNSIGNED NOT NULL  COMMENT '1=draft response, 0=submitted response',
  option_id   SMALLINT    UNSIGNED NOT NULL  COMMENT 'selection opton for a particular survey quesiton',
  UNIQUE KEY  (userid,survey_id,question_id,draft,option_id),
  FOREIGN KEY (userid,survey_id,question_id,draft) 
              REFERENCES tlc_srv_responses (userid,survey_id,question_id,draft)
              ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (survey_id,option_id) REFERENCES tlc_srv_survey_options(survey_id,option_id)
              ON UPDATE RESTRICT ON DELETE CASCADE
);
SQL ],

// Finally, build the cache tables that are used as temporary housing
//  during "remodeling" of the survey structure in the admin dashboard
  [ __LINE__, 'CREATE TABLE tlc_srv_user_status_cache      AS SELECT * FROM tlc_srv_user_status'      ],
  [ __LINE__, 'CREATE TABLE tlc_srv_responses_cache        AS SELECT * FROM tlc_srv_responses'        ],
  [ __LINE__, 'CREATE TABLE tlc_srv_response_options_cache AS SELECT * FROM tlc_srv_response_options' ],

// Now that we have tables defined, let's create some views
//   Some views are simply useful for database administration
//   Most views are used to simplify queries made from the app

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_draft_surveys
  AS SELECT * from tlc_srv_surveys
      WHERE active IS NULL;
SQL ],

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_active_surveys
  AS SELECT * from tlc_srv_surveys
      WHERE active IS NOT NULL AND closed IS NULL;
SQL ],

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_closed_surveys
  AS SELECT * from tlc_srv_surveys
      WHERE closed IS NOT NULL;
SQL ],

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_active_roles
  AS SELECT r.userid, u.fullname, r.admin, r.content, r.tech, r.summary
       FROM tlc_srv_roles r
       LEFT JOIN tlc_srv_userids u ON u.userid=r.userid
      WHERE r.content=1 OR r.admin=1 OR r.tech=1 OR r.summary=1;
SQL ],

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_view_questions AS
SELECT question_id, survey_id, wording, question_type, 
  CASE WHEN (question_flags & 0x01) > 0 THEN 'RIGHT'  ELSE 'LEFT' END AS alignment,
  CASE WHEN (question_flags & 0x02) > 0 THEN 'COLUMN' ELSE 'ROW'  END AS orientation,
  CASE WHEN question_type not like 'SELECT%' THEN NULL
       WHEN (question_flags & 0x04) > 0 THEN 'YES' ELSE 'NO' END AS has_other,
  other, qualifier, intro, info
FROM tlc_srv_questions;
SQL ],

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_view_question_options AS
SELECT q.survey_id,q.question_id, q.wording, 
       qo.sequence, qo.option_id, so.option_str, q.question_type
FROM tlc_srv_questions q
LEFT JOIN tlc_srv_question_options qo 
       ON qo.question_id=q.question_id and qo.survey_id=q.survey_id
LEFT JOIN tlc_srv_survey_options so 
       ON so.survey_id=qo.survey_id and so.option_id=qo.option_id
WHERE q.question_type like 'SELECT%';
SQL ],

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_view_responses_freetext AS
SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
       q.question_id, q.wording, r.free_text, r.qualifier
  FROM tlc_srv_responses r
  LEFT JOIN tlc_srv_questions q 
         ON q.question_id=r.question_id and q.survey_id=r.survey_id
 WHERE r.free_text is not NULL
   AND q.question_type='FREETEXT';
SQL ],

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_view_responses_bool AS
SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
       q.question_id, q.wording,
       CASE WHEN r.selected=0 THEN 'NO' ELSE 'YES' END AS selected,
       r.qualifier
  FROM tlc_srv_responses r
  LEFT JOIN tlc_srv_questions q 
         ON q.question_id=r.question_id and q.survey_id=r.survey_id
 WHERE r.selected is not NULL
   AND q.question_type='BOOL';
SQL ],

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_view_responses_select_one AS
SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
       q.question_id, q.wording,
       r.selected, 
       CASE WHEN r.selected = 0 THEN r.other ELSE so.option_str END as 'option', 
       r.qualifier
  FROM tlc_srv_responses r
  LEFT JOIN tlc_srv_questions q 
         ON q.question_id=r.question_id and q.survey_id=r.survey_id
 LEFT JOIN tlc_srv_question_options qo
         ON qo.survey_id=q.survey_id and qo.question_id=q.question_id and qo.sequence=r.selected
 LEFT JOIN tlc_srv_survey_options so
         ON so.survey_id=qo.survey_id and so.option_id=qo.option_id
 WHERE r.selected is not NULL
   AND q.question_type='SELECT_ONE';
SQL ],

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_view_responses_select_multi AS
SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
       q.question_id, q.wording,
       r.other, r.qualifier
  FROM tlc_srv_responses r
  LEFT JOIN tlc_srv_questions q 
         ON q.question_id=r.question_id and q.survey_id=r.survey_id
 WHERE q.question_type='SELECT_MULTI';
SQL ],

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_view_response_options AS
SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
       q.question_id, q.wording, so.option_str
  FROM tlc_srv_responses r
  LEFT JOIN tlc_srv_questions q 
         ON q.question_id=r.question_id and q.survey_id=r.survey_id
 LEFT JOIN tlc_srv_response_options ro
         ON ro.userid=r.userid and ro.survey_id=r.survey_id and ro.question_id=r.question_id
 LEFT JOIN tlc_srv_survey_options so
         ON so.survey_id=ro.survey_id and so.option_id=ro.option_id
 WHERE q.question_type='SELECT_MULTI'
   AND ro.option_id is not NULL;
SQL ],

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_view_last_user_survey AS
SELECT u.userid, su.survey_id, su.title as survey_name
  FROM tlc_srv_user_status AS u
  JOIN ( SELECT userid, MAX(submitted) AS max_submitted  
         FROM tlc_srv_user_status 
         WHERE survey_id NOT IN (SELECT survey_id FROM tlc_srv_active_surveys) 
         GROUP BY userid) AS uf
      ON u.userid = uf.userid AND u.submitted = uf.max_submitted
  JOIN ( SELECT survey_id,title FROM tlc_srv_surveys ) AS su ON u.survey_id = su.survey_id;
SQL ],

  [ __LINE__, <<<SQL
CREATE VIEW tlc_srv_view_unused_options AS
SELECT so.survey_id,so.option_id
  FROM tlc_srv_survey_options so
  LEFT JOIN tlc_srv_question_options qo
        ON qo.survey_id  = so.survey_id
       AND qo.option_id  = so.option_id
 WHERE qo.survey_id IS NULL;
SQL ],

];
