-- At one point, the version table was a crucial element in the
--   maintenance of database structure.  It is now just a record
--   of the scripts the SQL scripts that were run against it over
--   time.
CREATE TABLE tlc_tts_version_history (
  version VARCHAR(32) PRIMARY KEY,
  change_description VARCHAR(512) NOT NULL,
  added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

--- string length for title is enforced in the validate_survey_name function
---   in admin/js/surveys/metadata.js
CREATE TABLE tlc_tts_surveys (
  survey_id   smallint UNSIGNED NOT NULL,
  parent_id   smallint UNSIGNED DEFAULT NULL,
  title       varchar(128) NOT NULL,
  created     datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modified    datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  active      datetime DEFAULT NULL,
  closed      datetime DEFAULT NULL,
  PRIMARY KEY (survey_id),
  FOREIGN KEY (parent_id) REFERENCES tlc_tts_surveys(survey_id) ON UPDATE RESTRICT ON DELETE SET NULL
);

--- @@@ TODO... enforce the string length for the option string
CREATE TABLE tlc_tts_survey_options (
  survey_id  smallint UNSIGNED NOT NULL,
  option_id  smallint UNSIGNED NOT NULL COMMENT 'Provides continuity between surveys',
  option_str varchar(128)      NOT NULL COMMENT 'What will appear in the survey form',
  PRIMARY KEY (survey_id,option_id),
  FOREIGN KEY (survey_id) REFERENCES tlc_tts_surveys(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
);

--- string lengths for name, intro, and feedback are enforeced
---   in the Section Editor block in admin/survey_frame.php
CREATE TABLE tlc_tts_survey_sections (
  survey_id    smallint UNSIGNED NOT NULL,
  section_id   smallint UNSIGNED NOT NULL,
  sequence     smallint UNSIGNED NOT NULL     COMMENT 'Order this section will appear in the survey form.',
  name         varchar(128)                   COMMENT 'Section name that will appear in the editor and on survey tabs. NULL excludes this section from the survey',
  collapsible  tinyint  UNSIGNED DEFAULT NULL COMMENT 'Whether to include the name as a section header',
  intro        varchar(512)      DEFAULT NULL COMMENT 'Section intro that will appear in the survey form',
  feedback     varchar(128)      DEFAULT NULL COMMENT 'Text used to prompt for feedback. No feedback allowed if NULL',
  PRIMARY KEY (survey_id,section_id),
  UNIQUE  KEY (survey_id,sequence),
  FOREIGN KEY (survey_id)    REFERENCES tlc_tts_surveys(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
);

--- string lengths for wording, other, qualifier, into, and info are enforced
---   in the Question Editor block in admin/survey_frame.php
CREATE TABLE tlc_tts_survey_questions (
  question_id    smallint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Provides continuity between surveys',
  survey_id      smallint UNSIGNED NOT NULL,
  wording        varchar(128)      DEFAULT NULL       COMMENT 'The wording of this question shown in the survey (except for INFO)',
  question_type  ENUM('INFO','BOOL','OPTIONS','FREETEXT','SELECT_MULTI','SELECT_ONE') NOT NULL ,
  question_flags INT               NOT NULL DEFAULT 0 COMMENT 'See tlc_tts_view_survey_questions for details',
  other          varchar(45)       DEFAULT NULL       COMMENT 'For OPTIONS type, label to use in the survey for the "other" input field',
  qualifier      varchar(45)       DEFAULT NULL       COMMENT 'For OPTIONS/BOOL types, provide a text input field with the specified label',
  intro          varchar(512)      DEFAULT NULL       COMMENT 'For non-INFO types, provides a intro of the question on the survey',
  info           varchar(1024)     DEFAULT NULL       COMMENT 'Additional information about the question. For INFO, will appear on the form.  For all others, will appear in pop-ups.',
  PRIMARY KEY (question_id,survey_id),
  FOREIGN KEY (survey_id) REFERENCES tlc_tts_surveys(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
);
-- Notes:
-- There are four Survey question types:
--    INFO      Not a question, exists to provide info to the survey participants.
--    BOOL      Yes/No type question (probably will be implemented as a checkbox)
--    OPTIONS   Multiple choice (option) questions.
--    FREETEXT  Question where the participant can provide a free form written respone
-- For BOOL questions, layout specifies the order of the checkbox and label
--    LEFT      checkbox appears before the question
--    RIGHT     checkbox appears after the question
-- For OPTION questions, layout specifies how the options should appear
--    ROW       options appear in a single row after the question (wrapping if necessary)
--    LCOL      options appear in a left  aligned column with checkboxes before the option label
--    RCOL      options appear in a right aligned column with checkboxes after  the option label

CREATE TABLE tlc_tts_question_map (
  survey_id     smallint UNSIGNED NOT NULL,
  section_id    smallint UNSIGNED NOT NULL,
  question_seq  smallint UNSIGNED NOT NULL,
  question_id   smallint UNSIGNED NOT NULL,
  PRIMARY KEY (survey_id,section_id,question_seq),
  UNIQUE KEY  (survey_id,question_id),
  FOREIGN KEY (survey_id,section_id) REFERENCES tlc_tts_survey_sections (survey_id,section_id)
              ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (question_id,survey_id) REFERENCES tlc_tts_survey_questions (question_id,survey_id) 
              ON UPDATE RESTRICT ON DELETE CASCADE
);

CREATE TABLE tlc_tts_question_options (
  survey_id   smallint UNSIGNED NOT NULL,
  question_id smallint UNSIGNED NOT NULL,
  sequence    smallint UNSIGNED NOT NULL,
  option_id   smallint UNSIGNED NOT NULL,
  PRIMARY KEY (survey_id,question_id,sequence),
  UNIQUE  KEY (survey_id,question_id,option_id), 
  FOREIGN KEY (survey_id,question_id) REFERENCES tlc_tts_question_map(survey_id,question_id)
              ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (survey_id,option_id) REFERENCES tlc_tts_survey_options(survey_id,option_id)
              ON UPDATE RESTRICT ON DELETE CASCADE
);

CREATE TABLE tlc_tts_userids (
  userid   varchar(24)  PRIMARY KEY,
  fullname varchar(100) NOT NULL,
  email    varchar(45)  DEFAULT NULL,
  password varchar(64)  NOT NULL COMMENT 'hash of the password',
  admin    tinyint      UNSIGNED NOT NULL DEFAULT 0 COMMENT 'has admin permission'
);

CREATE TABLE tlc_tts_reset_tokens (
  userid    varchar(24)      NOT NULL PRIMARY KEY,
  token     varchar(20)      NOT NULL,
  expires   datetime         NOT NULL,
  FOREIGN KEY (userid) REFERENCES tlc_tts_userids(userid) ON UPDATE RESTRICT ON DELETE CASCADE
);

CREATE TABLE tlc_tts_roles (
  userid    varchar(24)         NOT NULL PRIMARY KEY,
  admin     tinyint     UNSIGNED NOT NULL DEFAULT 0,
  content   tinyint     UNSIGNED NOT NULL DEFAULT 0,
  tech      tinyint     UNSIGNED NOT NULL DEFAULT 0,
  summary   tinyint     UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (userid) REFERENCES tlc_tts_userids(userid) ON UPDATE RESTRICT ON DELETE CASCADE
);

create table tlc_tts_settings (
  name  varchar(24)  NOT NULL PRIMARY KEY,
  value varchar(255) NOT NULL
);

CREATE TABLE tlc_tts_user_status (
  userid      varchar(24)          NOT NULL,
  survey_id   smallint    UNSIGNED NOT NULL,
  draft       datetime             DEFAULT NULL,
  submitted   datetime             DEFAULT NULL,
  email_sent  datetime             DEFAULT NULL,
  sent_to     varchar(45)          DEFAULT NULL,
  PRIMARY KEY (userid,survey_id),
  FOREIGN KEY (userid)    REFERENCES tlc_tts_userids(userid)    ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (survey_id) REFERENCES tlc_tts_surveys(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
);

CREATE TABLE tlc_tts_responses (
  userid      varchar(24)          NOT NULL,
  survey_id   smallint    UNSIGNED NOT NULL,
  question_id smallint    UNSIGNED NOT NULL,
  draft       tinyint     UNSIGNED NOT NULL     COMMENT '1=draft response, 0=submitted response',
  selected    smallint    UNSIGNED DEFAULT NULL COMMENT '1/0 or select id based on question type',
  free_text   text                 DEFAULT NULL COMMENT 'reponse to free text questions',
  qualifier   text                 DEFAULT NULL COMMENT 'response qualifying information',
  other       varchar(128)         DEFAULT NULL COMMENT 'user provided other-option text',
  PRIMARY KEY (userid,survey_id,question_id,draft),
  FOREIGN KEY (userid,survey_id) REFERENCES tlc_tts_user_status(userid,survey_id) ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES tlc_tts_survey_questions(question_id) ON UPDATE RESTRICT ON DELETE CASCADE
);

CREATE TABLE tlc_tts_section_feedback (
  userid      varchar(24)          NOT NULL,
  survey_id   smallint    UNSIGNED NOT NULL,
  section_id  smallint    UNSIGNED NOT NULL,
  draft       tinyint     UNSIGNED NOT NULL     COMMENT '1=draft response, 0=submitted response',
  feedback    text                 DEFAULT NULL,
  PRIMARY KEY (userid,survey_id,section_id,draft),
  FOREIGN KEY (userid,survey_id) REFERENCES tlc_tts_user_status(userid,survey_id) ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (survey_id,section_id) REFERENCES tlc_tts_survey_sections(survey_id,section_id) ON UPDATE RESTRICT ON DELETE CASCADE
);

CREATE TABLE tlc_tts_response_options (
  userid      varchar(24)          NOT NULL,
  survey_id   smallint    UNSIGNED NOT NULL,
  question_id smallint    UNSIGNED NOT NULL,
  draft       tinyint     UNSIGNED NOT NULL  COMMENT '1=draft response, 0=submitted response',
  option_id   smallint    UNSIGNED NOT NULL  COMMENT 'selection opton for a particular survey quesiton',
  UNIQUE KEY  (userid,survey_id,question_id,draft,option_id),
  FOREIGN KEY (userid,survey_id,question_id,draft) 
              REFERENCES tlc_tts_responses (userid,survey_id,question_id,draft)
              ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (survey_id,option_id) REFERENCES tlc_tts_survey_options(survey_id,option_id)
              ON UPDATE RESTRICT ON DELETE CASCADE
);

CREATE TABLE tlc_tts_reminder_emails (
  userid    varchar(24) NOT NULL,
  subject   varchar(32) NOT NULL,
  last_sent datetime    NOT NULL,
  email     varchar(45) NOT NULL,
  PRIMARY KEY (userid),
  FOREIGN KEY (userid) REFERENCES tlc_tts_userids(userid) on UPDATE RESTRICT ON DELETE CASCADE
);

CREATE TABLE tlc_tts_access_tokens (
  userid   varchar(24)  NOT NULL,
  token    varchar(45)  NOT NULL COMMENT 'access token',
  expires  datetime     NOT NULL COMMENT 'when the token expires unless renewed',
  PRIMARY KEY (userid,token),
  FOREIGN KEY (userid) REFERENCES tlc_tts_userids(userid) on UPDATE RESTRICT ON DELETE CASCADE
);

CREATE VIEW tlc_tts_draft_surveys
  AS SELECT * from tlc_tts_surveys
      WHERE active IS NULL;

CREATE VIEW tlc_tts_active_surveys
  AS SELECT * from tlc_tts_surveys
      WHERE active IS NOT NULL AND closed IS NULL;

CREATE VIEW tlc_tts_closed_surveys
  AS SELECT * from tlc_tts_surveys
      WHERE closed IS NOT NULL;

CREATE VIEW tlc_tts_user_reset_tokens
  AS SELECT u.userid, t.token, t.expires
       FROM tlc_tts_userids u, tlc_tts_reset_tokens t
      WHERE u.userid = t.userid;
      
CREATE VIEW tlc_tts_active_roles
  AS SELECT r.userid, u.fullname, r.admin, r.content, r.tech, r.summary
       FROM tlc_tts_roles r
       LEFT JOIN tlc_tts_userids u ON u.userid=r.userid
      WHERE r.content=1 OR r.admin=1 OR r.tech=1 OR r.summary=1;

CREATE VIEW tlc_tts_view_survey_questions AS
SELECT question_id, survey_id, wording, question_type, 
  CASE WHEN (question_flags & 0x01) > 0 THEN 'RIGHT'  ELSE 'LEFT' END AS alignment,
  CASE WHEN (question_flags & 0x02) > 0 THEN 'COLUMN' ELSE 'ROW'  END AS orientation,
  CASE WHEN (question_flags & 0x08) > 0 THEN 'YES' 
       WHEN (question_flags & 0x10) > 0 THEN 'NEW' 
       ELSE 'NO'
       END AS grouped,
  CASE WHEN question_type not like 'SELECT%' THEN NULL
       WHEN (question_flags & 0x04) > 0 THEN 'YES' ELSE 'NO' END AS has_other,
  other, qualifier, intro, info
FROM tlc_tts_survey_questions;

CREATE VIEW tlc_tts_view_question_options AS
SELECT q.survey_id,q.question_id, q.wording, 
       qo.sequence, qo.option_id, so.option_str, q.question_type
FROM tlc_tts_survey_questions q
LEFT JOIN tlc_tts_question_options qo 
       ON qo.question_id=q.question_id and qo.survey_id=q.survey_id
LEFT JOIN tlc_tts_survey_options so 
       ON so.survey_id=qo.survey_id and so.option_id=qo.option_id
WHERE q.question_type like 'SELECT%';

CREATE VIEW tlc_tts_view_responses_freetext AS
SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
       q.question_id, q.wording, r.free_text, r.qualifier
  FROM tlc_tts_responses r
  LEFT JOIN tlc_tts_survey_questions q 
         ON q.question_id=r.question_id and q.survey_id=r.survey_id
 WHERE r.free_text is not NULL
   AND q.question_type='FREETEXT';

CREATE VIEW tlc_tts_view_responses_bool AS
SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
       q.question_id, q.wording,
       CASE WHEN r.selected=0 THEN 'NO' ELSE 'YES' END AS selected,
       r.qualifier
  FROM tlc_tts_responses r
  LEFT JOIN tlc_tts_survey_questions q 
         ON q.question_id=r.question_id and q.survey_id=r.survey_id
 WHERE r.selected is not NULL
   AND q.question_type='BOOL';

CREATE VIEW tlc_tts_view_responses_select_one AS
SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
       q.question_id, q.wording,
       r.selected, 
       CASE WHEN r.selected = 0 THEN r.other ELSE so.option_str END as 'option', 
       r.qualifier
  FROM tlc_tts_responses r
  LEFT JOIN tlc_tts_survey_questions q 
         ON q.question_id=r.question_id and q.survey_id=r.survey_id
 LEFT JOIN tlc_tts_question_options qo
         ON qo.survey_id=q.survey_id and qo.question_id=q.question_id and qo.sequence=r.selected
 LEFT JOIN tlc_tts_survey_options so
         ON so.survey_id=qo.survey_id and so.option_id=qo.option_id
 WHERE r.selected is not NULL
   AND q.question_type='SELECT_ONE';

CREATE VIEW tlc_tts_view_responses_select_multi AS
SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
       q.question_id, q.wording,
       r.other, r.qualifier
  FROM tlc_tts_responses r
  LEFT JOIN tlc_tts_survey_questions q 
         ON q.question_id=r.question_id and q.survey_id=r.survey_id
 WHERE q.question_type='SELECT_MULTI';

CREATE VIEW tlc_tts_view_response_options AS
SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
       q.question_id, q.wording, so.option_str
  FROM tlc_tts_responses r
  LEFT JOIN tlc_tts_survey_questions q 
         ON q.question_id=r.question_id and q.survey_id=r.survey_id
 LEFT JOIN tlc_tts_response_options ro
         ON ro.userid=r.userid and ro.survey_id=r.survey_id and ro.question_id=r.question_id
 LEFT JOIN tlc_tts_survey_options so
         ON so.survey_id=ro.survey_id and so.option_id=ro.option_id
 WHERE q.question_type='SELECT_MULTI'
   AND ro.option_id is not NULL;

CREATE VIEW tlc_tts_view_last_user_survey AS
SELECT u.userid, su.survey_id, su.title as survey_name
  FROM tlc_tts_user_status AS u
  JOIN ( SELECT userid, MAX(submitted) AS max_submitted  
         FROM tlc_tts_user_status 
         WHERE survey_id NOT IN (SELECT survey_id FROM tlc_tts_active_surveys) 
         GROUP BY userid) AS uf
      ON u.userid = uf.userid AND u.submitted = uf.max_submitted
  JOIN ( SELECT survey_id,title FROM tlc_tts_surveys ) AS su ON u.survey_id = su.survey_id;

CREATE VIEW tlc_tts_view_unused_options AS
SELECT so.survey_id,so.option_id
  FROM tlc_tts_survey_options so
  LEFT JOIN tlc_tts_question_options qo
        ON qo.survey_id  = so.survey_id
       AND qo.option_id  = so.option_id
 WHERE qo.survey_id IS NULL;

INSERT INTO tlc_tts_version_history (version, change_description)
VALUES ('1.1.0', 'Initial Database Configuration');
