-- This script handles both the initial creation and subsequent
--   version upgrades to the tables and views required for the 
--   tlc-ttsurvey app.
--
--  The version history is captured in the table tlc_tt_version_history.
--    Do NOT mess with this table if you don't want potentially bad things
--    to happen to all of the other tlc-ttsurvey tables.
--
--  How this script works:
--    - If the version history table does not exist, it creates it
--
--    - The current version is read from the version table
--        If the version table doesn't exist, it creates it and sets
--        the current version to 0.
--
--    - If the current version is 0:
--        - the version 1 creation of all tables/views is executed
--        - the current version is set to 1 and added to the version history
--    - If the current version is 1 (including if it was JUST set to 1):
--        - the version 2 modifications of tables/views is executed
--        - the current version is set to 2 and added to the version history
--    ...
--    - If the current version is N (including if it was JUST set to N):
--        - the version (N+1) modifications of tables/views is executed
--        - the current version is set to (N+1) and added to the version history
--
--    - All stored procedures associated with this script are dropped
--
--
--  IMPORTANT NOTES TO MAINTAINERS OF THIS SCRIPT --
--  
--    - Once a version of this script has been merged into master, DO NOT
--        MODIFY any of the prior versions in any way other than to fix
--        errors reported by mysql itself.
-- 
--    - When adding a new version, always include the following as the 
--        LAST thing in your block of commands:
--
--           INSERT INTO tlc_tt_version_history (description) VALUES ('some description');
--           SET version = version + 1
-- 
--   - To help other maintainers, add a comment in the procedure inside your
--       version's if block describing what the changes are and why they
--       were made.


-- HERE WE GO... 

-- If you don't understand the following statement, please read up on creating
-- stored procedures before modifying this script!!!
DELIMITER //

-- just in case the last run didn't clean up properly
DROP PROCEDURE IF EXISTS tlc_tt_upgrade_tables;

-- start the procedure creation
CREATE PROCEDURE tlc_tt_upgrade_tables ()

BEGIN

-- current_version keeps track of the current version throughout the procedure
DECLARE current_version INT DEFAULT 0;

-- if the version table doesn't yet exist, create it
CREATE TABLE IF NOT EXISTS tlc_tt_version_history (
	version INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description VARCHAR(45) NOT NULL,
  added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);
    
-- see if there are any rows in the table.
-- if not, then we are at version 0 (initial table creation)
SELECT IFNULL(MAX(version),0) INTO current_version FROM tlc_tt_version_history;

--
-- VERSION 1 --
--
IF current_version < 1 THEN
-- This is the inital setup of the tlc-ttsurvey tables/views
--
-- None of the tables we're creating here should already exist.
--   If they do, we're just going to abort this script until things have 
--   been straightened out.  We don't want to clobber a table created for
--   a purpose other than the tlc-ttsurvey app.  Therefore, we do not use
--   the "if not exists" clause in any of the create statements below.

  CREATE TABLE tlc_tt_strings (
    string_id smallint      UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    str       varchar(1024) NOT NULL,
    str_hash  binary(32)    GENERATED ALWAYS AS (UNHEX(SHA2(str, 256))) STORED,
    UNIQUE KEY (str_hash)
  );

  CREATE TABLE tlc_tt_survey_status (
    survey_id   smallint UNSIGNED NOT NULL,
    parent_id   smallint UNSIGNED DEFAULT NULL,
    created     datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    active      datetime DEFAULT NULL,
    closed      datetime DEFAULT NULL,
    PRIMARY KEY (survey_id),
    FOREIGN KEY (parent_id) REFERENCES tlc_tt_survey_status(survey_id) ON UPDATE RESTRICT ON DELETE SET NULL
  );

  CREATE TABLE tlc_tt_survey_revisions (
    survey_id   smallint UNSIGNED NOT NULL,
    survey_rev  smallint UNSIGNED NOT NULL DEFAULT 1,
    title_sid   smallint UNSIGNED NOT NULL COMMENT '(StringID) survey title',
    modified    datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (survey_id,survey_rev),
    FOREIGN KEY (survey_id) REFERENCES tlc_tt_survey_status(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (title_sid) REFERENCES tlc_tt_strings(string_id)       ON UPDATE RESTRICT ON DELETE RESTRICT
  );

  CREATE TABLE tlc_tt_survey_options (
    survey_id  smallint UNSIGNED NOT NULL,
    survey_rev smallint UNSIGNED NOT NULL,
    option_id  smallint UNSIGNED NOT NULL COMMENT 'Provides continuity between surveys',
    text_sid   smallint UNSIGNED NOT NULL COMMENT '(StringID) What will appear in the survey form',
    PRIMARY KEY (survey_id,survey_rev,option_id),
    FOREIGN KEY (survey_id,survey_rev) 
                REFERENCES tlc_tt_survey_revisions(survey_id,survey_rev)
                ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (text_sid)  REFERENCES tlc_tt_strings(string_id) ON UPDATE RESTRICT ON DELETE RESTRICT
  );

  CREATE TABLE tlc_tt_survey_sections (
    survey_id    smallint UNSIGNED NOT NULL,
    survey_rev   smallint UNSIGNED NOT NULL,
    sequence     smallint UNSIGNED NOT NULL     COMMENT 'Order this section will appear in the survey form.',
    name_sid     smallint UNSIGNED              COMMENT '(StringID) Section name that will appear in the editor and on survey tabs. NULL excludes this section from the survey',
    collapsible  tinyint  UNSIGNED DEFAULT NULL COMMENT 'Whether to include the name as a section header',
    intro_sid    smallint UNSIGNED DEFAULT NULL COMMENT '(StringID) Section intro that will appear in the survey form',
    feedback_sid smallint UNSIGNED DEFAULT NULL COMMENT '(StringID) Text used to prompt for feedback. No feedback allowed if NULL',
    PRIMARY KEY (survey_id,survey_rev,sequence),
    FOREIGN KEY (name_sid)     REFERENCES tlc_tt_strings(string_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (intro_sid)    REFERENCES tlc_tt_strings(string_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (feedback_sid) REFERENCES tlc_tt_strings(string_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (survey_id,survey_rev) 
                REFERENCES tlc_tt_survey_revisions(survey_id,survey_rev) 
                ON UPDATE RESTRICT ON DELETE CASCADE
  );

  CREATE TABLE tlc_tt_survey_questions (
    question_id   smallint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Provides continuity between surveys',
    survey_id     smallint UNSIGNED NOT NULL,
    survey_rev    smallint UNSIGNED NOT NULL,
    wording_sid   smallint UNSIGNED DEFAULT NULL       COMMENT '(StringID) The wording of this question shown in the survey (except for INFO)',
    question_type ENUM('INFO','BOOL','OPTIONS','FREETEXT') NOT NULL ,
    multiple      tinyint           DEFAULT NULL       COMMENT 'For OPTIONS type, multiple options can be selected',
    layout        ENUM('LEFT','RIGHT','ROW','LCOL','RCOL') DEFAULT NULL
                                                       COMMENT 'For OPTION types: ROW, LCOL, RCOL. For BOOL types: LEFT, RIGHT',
    other_flag    tinyint  UNSIGNED DEFAULT NULL       COMMENT 'For OPTIONS type, allow user to write in an "other" value',
    other_sid     smallint UNSIGNED DEFAULT NULL       COMMENT '(StringID) For OPTIONS type, label to use in the survey for the "other" input field',
    qualifier_sid smallint UNSIGNED DEFAULT NULL       COMMENT '(StringID) For OPTIONS/BOOL types, provide a text input field with the specified label',
    intro_sid     smallint UNSIGNED DEFAULT NULL       COMMENT '(StringID) For non-INFO types, provides a intro of the question on the survey',
    info_sid      smallint UNSIGNED DEFAULT NULL       COMMENT '(StringID) Additional information about the question. For INFO, will appear on the form.  For all others, will appear in pop-ups.',
    PRIMARY KEY (question_id,survey_id,survey_rev),
    FOREIGN KEY (wording_sid)   REFERENCES tlc_tt_strings(string_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (other_sid)     REFERENCES tlc_tt_strings(string_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (qualifier_sid) REFERENCES tlc_tt_strings(string_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (intro_sid)     REFERENCES tlc_tt_strings(string_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (info_sid)      REFERENCES tlc_tt_strings(string_id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (survey_id,survey_rev) 
                REFERENCES tlc_tt_survey_revisions (survey_id,survey_rev)
                ON UPDATE RESTRICT ON DELETE CASCADE
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

  CREATE TABLE tlc_tt_question_map (
    survey_id     smallint UNSIGNED NOT NULL,
    survey_rev    smallint UNSIGNED NOT NULL,
    section_seq   smallint UNSIGNED NOT NULL,
    question_seq  smallint UNSIGNED NOT NULL,
    question_id   smallint UNSIGNED NOT NULL,
    PRIMARY KEY (survey_id,survey_rev,section_seq,question_seq),
    UNIQUE KEY  (survey_id,survey_rev,question_id),
    FOREIGN KEY (survey_id,survey_rev,section_seq) REFERENCES tlc_tt_survey_sections (survey_id,survey_rev,sequence)
      ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (question_id,survey_id,survey_rev) REFERENCES tlc_tt_survey_questions (question_id,survey_id,survey_rev) ON UPDATE RESTRICT ON DELETE CASCADE
  );

  CREATE TABLE tlc_tt_question_options (
    survey_id   smallint UNSIGNED NOT NULL,
    survey_rev  smallint UNSIGNED NOT NULL,
    question_id smallint UNSIGNED NOT NULL,
    sequence    smallint UNSIGNED NOT NULL,
    option_id   smallint UNSIGNED NOT NULL,
    PRIMARY KEY (survey_id,survey_rev,question_id,sequence),
    UNIQUE  KEY (survey_id,survey_rev,question_id,option_id), 
    FOREIGN KEY (survey_id,survey_rev,question_id)
                REFERENCES tlc_tt_question_map(survey_id,survey_rev,question_id)
                ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (survey_id,survey_rev,option_id)
                REFERENCES tlc_tt_survey_options(survey_id,survey_rev,option_id)
                ON UPDATE RESTRICT ON DELETE CASCADE
  );

  CREATE TABLE tlc_tt_userids (
    userid   varchar(24)  PRIMARY KEY,
    fullname varchar(100) NOT NULL,
    email    varchar(45)  DEFAULT NULL,
    token    varchar(45)  NOT NULL COMMENT 'access token',
    password varchar(64)  NOT NULL COMMENT 'hash of the password',
    anonid   varchar(64)  NOT NULL COMMENT 'hash of the anonid or userid',
    admin    tinyint      UNSIGNED NOT NULL DEFAULT 0 COMMENT 'has admin permission'
    );

  CREATE TABLE tlc_tt_anonids (
    anonid    varchar(24) UNIQUE
  );

  CREATE TABLE tlc_tt_reset_tokens (
    userid    varchar(24)      NOT NULL PRIMARY KEY,
    token     varchar(20)      NOT NULL,
    expires   datetime         NOT NULL,
    FOREIGN KEY (userid) REFERENCES tlc_tt_userids(userid) ON UPDATE RESTRICT ON DELETE CASCADE
  );

  CREATE TABLE tlc_tt_roles (
    userid    varchar(24)         NOT NULL PRIMARY KEY,
    admin     tinyint     UNSIGNED NOT NULL DEFAULT 0,
    content   tinyint     UNSIGNED NOT NULL DEFAULT 0,
    tech      tinyint     UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (userid) REFERENCES tlc_tt_userids(userid) ON UPDATE RESTRICT ON DELETE CASCADE
  );

  create table tlc_tt_settings (
    name  varchar(24)  NOT NULL PRIMARY KEY,
    value varchar(255) NOT NULL
  );

  CREATE OR REPLACE VIEW tlc_tt_surveys
    AS SELECT s.survey_id, r.survey_rev AS survey_rev, t.str AS title,
              s.created, r.modified, s.active, s.closed,
              s.parent_id
         FROM tlc_tt_survey_status s
    LEFT JOIN ( select survey_id, max(survey_rev) max_rev 
                  from tlc_tt_survey_revisions 
                 group by survey_id ) f ON f.survey_id = s.survey_id
    LEFT JOIN tlc_tt_survey_revisions r ON r.survey_id = f.survey_id and r.survey_rev = f.max_rev
    LEFT JOIN tlc_tt_strings          t ON t.string_id = r.title_sid;

  CREATE OR REPLACE VIEW tlc_tt_draft_surveys
    AS SELECT * from tlc_tt_surveys
        WHERE active IS NULL;

  CREATE OR REPLACE VIEW tlc_tt_active_surveys
    AS SELECT * from tlc_tt_surveys
        WHERE active IS NOT NULL AND closed IS NULL;

  CREATE OR REPLACE VIEW tlc_tt_closed_surveys
    AS SELECT * from tlc_tt_surveys
        WHERE closed IS NOT NULL;

  CREATE OR REPLACE VIEW tlc_tt_user_reset_tokens
    AS SELECT u.userid, t.token, t.expires
         FROM tlc_tt_userids u, tlc_tt_reset_tokens t
        WHERE u.userid = t.userid;
        
  CREATE OR REPLACE VIEW tlc_tt_active_roles
    AS SELECT r.userid, u.fullname, r.admin, r.content, r.tech
         FROM tlc_tt_roles r
         LEFT JOIN tlc_tt_userids u ON u.userid=r.userid
        WHERE r.content=1 OR r.admin=1 OR r.tech=1;

  CREATE OR REPLACE VIEW tlc_tt_view_survey_sections AS
  SELECT s.survey_id, s.survey_rev, s.sequence,
    s.name_sid,        name.str     AS name_str,
    s.collapsible,
    s.intro_sid,       intro.str    AS intro_str,
    s.feedback_sid,    feedback.str AS feedback_str
  FROM tlc_tt_survey_sections s
  LEFT JOIN tlc_tt_strings name     ON s.name_sid = name.string_id
  LEFT JOIN tlc_tt_strings intro    ON s.intro_sid = intro.string_id
  LEFT JOIN tlc_tt_strings feedback ON s.feedback_sid = feedback.string_id;

  CREATE OR REPLACE VIEW tlc_tt_view_survey_questions AS
  SELECT q.question_id, q.survey_id, q.survey_rev, 
    q.wording_sid,     wording.str     AS wording_str,
    q.question_type, 
    q.multiple,
    q.layout,
    q.other_flag,
    q.other_sid,       other.str       AS other_str,
    q.qualifier_sid,   qualifier.str   AS qualifier_str,
    q.intro_sid,       intro.str       AS intro_str,
    q.info_sid,        info.str        AS info_str
  FROM tlc_tt_survey_questions q
  LEFT JOIN tlc_tt_strings wording     ON q.wording_sid = wording.string_id
  LEFT JOIN tlc_tt_strings other       ON q.other_sid = other.string_id
  LEFT JOIN tlc_tt_strings qualifier   ON q.qualifier_sid = qualifier.string_id
  LEFT JOIN tlc_tt_strings intro       ON q.intro_sid = intro.string_id
  LEFT JOIN tlc_tt_strings info        ON q.info_sid = info.string_id;

  CREATE OR REPLACE VIEW tlc_tt_view_survey_options AS
  SELECT o.survey_id, o.option_id, o.survey_rev, o.text_sid, text.str AS text_str
  FROM tlc_tt_survey_options o
  LEFT JOIN tlc_tt_strings text ON o.text_sid = text.string_id;

  CREATE OR REPLACE VIEW tlc_tt_view_question_options AS
  SELECT q.survey_id,q.survey_rev,q.question_id, w.str AS wording, qo.sequence, qo.option_id, os.str AS option_str, q.multiple
  FROM tlc_tt_survey_questions q
  LEFT JOIN tlc_tt_question_options qo on qo.question_id=q.question_id and qo.survey_id=q.survey_id and qo.survey_rev = q.survey_rev
  LEFT JOIN tlc_tt_survey_options so on so.survey_id=qo.survey_id and so.survey_rev=qo.survey_rev and so.option_id=qo.option_id
  LEFT JOIN tlc_tt_strings w on w.string_id = q.wording_sid
  LEFT JOIN tlc_tt_strings os on os.string_id = so.text_sid
  WHERE q.question_type = 'OPTIONS';


-- Add version 1 to the history and increment current version
  SET current_version = 1;
  INSERT INTO tlc_tt_version_history (version,description) values (current_version,"Initial Setup");
END IF;


--
-- VERSION 2 --
--
-- Replaces the OPTIONS question type with SELECT_MULTI and SELECT_ONE
--   and eliminates the 'multiple' column
--
-- Consolidates all question qualfier flag type columns into a single 'question_flags' column.
--   See the the tlc_tt_view_survey_questions view for interpreting the question_flags values.
--
IF current_version < 2 THEN

  ALTER TABLE tlc_tt_survey_questions
  CHANGE COLUMN question_type question_type ENUM('INFO', 'BOOL', 'OPTIONS', 'FREETEXT', 'SELECT_MULTI', 'SELECT_ONE') NOT NULL ;
   
  UPDATE tlc_tt_survey_questions set question_type='SELECT_MULTI' where question_type = 'OPTIONS' and multiple=1;
  UPDATE tlc_tt_survey_questions set question_type='SELECT_ONE' where question_type = 'OPTIONS' and ( multiple=0 or multiple is NULL);
  
  ALTER TABLE tlc_tt_survey_questions
  CHANGE COLUMN question_type question_type ENUM('INFO', 'BOOL', 'FREETEXT', 'SELECT_MULTI', 'SELECT_ONE') NOT NULL ;
  
  ALTER TABLE tlc_tt_survey_questions
  DROP COLUMN `multiple`;
  
  ALTER TABLE tlc_tt_survey_questions 
  ADD COLUMN question_flags INT NOT NULL DEFAULT 0 COMMENT 'See tlc_tt_view_survey_questions for details' AFTER layout;
  
  update tlc_tt_survey_questions set question_flags=1 where layout='RIGHT';
  update tlc_tt_survey_questions set question_flags=2 where layout='LCOL';
  update tlc_tt_survey_questions set question_flags=3 where layout='RCOL';

  update tlc_tt_survey_questions set question_flags=4+question_flags where other_flag = 1;
  
  ALTER TABLE tlc_tt_survey_questions DROP COLUMN layout;
  ALTER TABLE tlc_tt_survey_questions DROP COLUMN other_flag;
  
  CREATE OR REPLACE VIEW tlc_tt_view_survey_questions AS
  SELECT q.question_id, q.survey_id, q.survey_rev, 
    q.wording_sid,     wording.str     AS wording_str,
    q.question_type, 
    CASE WHEN (q.question_flags & 0x01) > 0 THEN 'RIGHT'  ELSE 'LEFT' END AS alignment,
    CASE WHEN (q.question_flags & 0x02) > 0 THEN 'COLUMN' ELSE 'ROW'  END AS orientation,
    CASE WHEN (q.question_flags & 0x08) > 0 THEN 'YES' 
         WHEN (q.question_flags & 0x10) > 0 THEN 'BOXED' 
         ELSE 'NO'
         END AS grouped,
    CASE WHEN q.question_type not like 'SELECT%' THEN NULL
         WHEN (q.question_flags & 0x04) > 0 THEN 'YES' ELSE 'NO' END AS has_other,
    q.other_sid,       other.str       AS other_str,
    q.qualifier_sid,   qualifier.str   AS qualifier_str,
    q.intro_sid,       intro.str       AS intro_str,
    q.info_sid,        info.str        AS info_str
  FROM tlc_tt_survey_questions q
  LEFT JOIN tlc_tt_strings wording     ON q.wording_sid = wording.string_id
  LEFT JOIN tlc_tt_strings other       ON q.other_sid = other.string_id
  LEFT JOIN tlc_tt_strings qualifier   ON q.qualifier_sid = qualifier.string_id
  LEFT JOIN tlc_tt_strings intro       ON q.intro_sid = intro.string_id
  LEFT JOIN tlc_tt_strings info        ON q.info_sid = info.string_id;

  CREATE OR REPLACE VIEW tlc_tt_view_question_options AS
  SELECT q.survey_id,q.survey_rev,q.question_id, w.str AS wording, 
         qo.sequence, qo.option_id, os.str AS option_str, q.question_type
  FROM tlc_tt_survey_questions q
  LEFT JOIN tlc_tt_question_options qo 
         ON qo.question_id=q.question_id and qo.survey_id=q.survey_id and qo.survey_rev = q.survey_rev
  LEFT JOIN tlc_tt_survey_options so 
         ON so.survey_id=qo.survey_id and so.survey_rev=qo.survey_rev and so.option_id=qo.option_id
  LEFT JOIN tlc_tt_strings w ON w.string_id = q.wording_sid
  LEFT JOIN tlc_tt_strings os ON os.string_id = so.text_sid
  WHERE q.question_type like 'SELECT%';

-- Add version 2 to the history and increment current version
  SET current_version = 2;
  INSERT INTO tlc_tt_version_history (version,description) values (current_version,"Consolidated Question Flags");

END IF;

--
-- VERSION 3 --
--
-- Finally adds the tables that will capture the user responses
--
IF current_version < 3 THEN

  CREATE TABLE tlc_tt_user_status (
    userid      varchar(24)          NOT NULL,
    survey_id   smallint    UNSIGNED NOT NULL,
    draft       datetime             DEFAULT NULL,
    submitted   datetime             DEFAULT NULL,
    email_sent  datetime             DEFAULT NULL,
    sent_to     varchar(45)          DEFAULT NULL,
    PRIMARY KEY (userid,survey_id),
    FOREIGN KEY (userid)    REFERENCES tlc_tt_userids(userid)          ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (survey_id) REFERENCES tlc_tt_survey_status(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
  );

  CREATE TABLE tlc_tt_responses (
    userid      varchar(24)          NOT NULL,
    survey_id   smallint    UNSIGNED NOT NULL,
    question_id smallint    UNSIGNED NOT NULL,
    draft       tinyint     UNSIGNED NOT NULL     COMMENT '1=draft response, 0=submitted response',
    selected    smallint    UNSIGNED DEFAULT NULL COMMENT '1/0 or option_id based on question type',
    free_text   text                 DEFAULT NULL COMMENT 'reponse to free text questions',
    qualifier   text                 DEFAULT NULL COMMENT 'response qualifying information',
    other       varchar(128)         DEFAULT NULL COMMENT 'user provided other-option text',
    PRIMARY KEY (userid,survey_id,question_id,draft),
    FOREIGN KEY (userid,survey_id) REFERENCES tlc_tt_user_status(userid,survey_id) ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES tlc_tt_survey_questions(question_id) ON UPDATE RESTRICT ON DELETE CASCADE
  );

  BEGIN
    DECLARE CONTINUE HANDLER FOR 1061 BEGIN END;
    ALTER TABLE tlc_tt_survey_sections ADD INDEX idx_survey_section (survey_id, sequence);
  END;

  CREATE TABLE tlc_tt_section_feedback (
    userid      varchar(24)          NOT NULL,
    survey_id   smallint    UNSIGNED NOT NULL,
    sequence    smallint    UNSIGNED NOT NULL,
    draft       tinyint     UNSIGNED NOT NULL     COMMENT '1=draft response, 0=submitted response',
    feedback    text                 DEFAULT NULL,
    PRIMARY KEY (userid,survey_id,sequence,draft),
    FOREIGN KEY (userid,survey_id) REFERENCES tlc_tt_user_status(userid,survey_id) ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (survey_id,sequence) REFERENCES tlc_tt_survey_sections(survey_id,sequence) ON UPDATE RESTRICT ON DELETE CASCADE
  );

  BEGIN
    DECLARE CONTINUE HANDLER FOR 1061 BEGIN END;
    ALTER TABLE tlc_tt_survey_options ADD INDEX idx_survey_option (survey_id, option_id);
  END;

  CREATE TABLE tlc_tt_response_options (
    userid      varchar(24)          NOT NULL,
    survey_id   smallint    UNSIGNED NOT NULL,
    question_id smallint    UNSIGNED NOT NULL,
    draft       tinyint     UNSIGNED NOT NULL  COMMENT '1=draft response, 0=submitted response',
    option_id   smallint    UNSIGNED NOT NULL  COMMENT 'selection opton for a particular survey quesiton',
    UNIQUE KEY  (userid,survey_id,question_id,draft,option_id),
    FOREIGN KEY (userid,survey_id,question_id,draft) 
                REFERENCES tlc_tt_responses (userid,survey_id,question_id,draft)
                ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (survey_id,option_id) REFERENCES tlc_tt_survey_options(survey_id,option_id)
                ON UPDATE RESTRICT ON DELETE CASCADE
  );

  CREATE OR REPLACE VIEW tlc_tt_view_responses_freetext AS
  SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
         q.question_id, wording.str as question,
         r.free_text, r.qualifier
    FROM tlc_tt_responses r
    LEFT JOIN tlc_tt_survey_questions q 
           ON q.question_id=r.question_id and q.survey_id=r.survey_id
    LEFT JOIN tlc_tt_strings wording ON q.wording_sid = wording.string_id
   WHERE r.free_text is not NULL
     AND q.survey_rev = (SELECT max(survey_rev) from tlc_tt_survey_revisions x where x.survey_id=q.survey_id)
     AND q.question_type='FREETEXT';

  CREATE OR REPLACE VIEW tlc_tt_view_responses_bool AS
  SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
         q.question_id, wording.str as question,
         CASE WHEN r.selected=0 THEN 'NO' ELSE 'YES' END AS selected,
         r.qualifier
    FROM tlc_tt_responses r
    LEFT JOIN tlc_tt_survey_questions q 
           ON q.question_id=r.question_id and q.survey_id=r.survey_id
    LEFT JOIN tlc_tt_strings wording ON q.wording_sid = wording.string_id
   WHERE r.selected is not NULL
     AND q.survey_rev = (SELECT max(survey_rev) from tlc_tt_survey_revisions x where x.survey_id=q.survey_id)
     AND q.question_type='BOOL';

  CREATE OR REPLACE VIEW tlc_tt_view_responses_select_one AS
  SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
         q.question_id, wording.str as question,
         r.selected, 
         CASE WHEN r.selected = 0 THEN r.other ELSE opt.str END as 'option', 
         r.qualifier
    FROM tlc_tt_responses r
    LEFT JOIN tlc_tt_survey_questions q 
           ON q.question_id=r.question_id and q.survey_id=r.survey_id
	  LEFT JOIN tlc_tt_question_options qo
           ON qo.survey_id=q.survey_id and qo.survey_rev=q.survey_rev and qo.question_id=q.question_id and qo.sequence=r.selected
	  LEFT JOIN tlc_tt_survey_options so
           ON so.survey_id=qo.survey_id and so.survey_rev=qo.survey_rev and so.option_id=qo.option_id
    LEFT JOIN tlc_tt_strings wording ON q.wording_sid = wording.string_id
    LEFT JOIN tlc_tt_strings opt     ON so.text_sid = opt.string_id
   WHERE r.selected is not NULL
     AND q.survey_rev = (SELECT max(survey_rev) from tlc_tt_survey_revisions x where x.survey_id=q.survey_id)
     AND q.question_type='SELECT_ONE';

  CREATE OR REPLACE VIEW tlc_tt_view_responses_select_multi AS
  SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
         q.question_id, wording.str as question,
         r.other, r.qualifier
    FROM tlc_tt_responses r
    LEFT JOIN tlc_tt_survey_questions q 
           ON q.question_id=r.question_id and q.survey_id=r.survey_id
    LEFT JOIN tlc_tt_strings wording ON q.wording_sid = wording.string_id
   WHERE survey_rev = (SELECT max(survey_rev) from tlc_tt_survey_revisions x where x.survey_id=q.survey_id)
     AND q.question_type='SELECT_MULTI';

   CREATE OR REPLACE VIEW tlc_tt_view_response_options AS
   SELECT r.userid, r.survey_id, CASE WHEN r.draft=0 THEN 'SUBMITTED' ELSE 'DRAFT' END AS status,
          q.question_id, wording.str as question, opt.str
     FROM tlc_tt_responses r
     LEFT JOIN tlc_tt_survey_questions q 
            ON q.question_id=r.question_id and q.survey_id=r.survey_id
	   LEFT JOIN tlc_tt_response_options ro
            ON ro.userid=r.userid and ro.survey_id=r.survey_id and ro.question_id=r.question_id
	   LEFT JOIN tlc_tt_survey_options so
            ON so.survey_id=ro.survey_id and so.survey_rev=q.survey_rev and so.option_id=ro.option_id
     LEFT JOIN tlc_tt_strings wording ON q.wording_sid = wording.string_id
	   LEFT JOIN tlc_tt_strings opt ON opt.string_id = so.text_sid
    WHERE q.survey_rev = (SELECT max(survey_rev) from tlc_tt_survey_revisions x where x.survey_id=q.survey_id)
      AND q.question_type='SELECT_MULTI'
      AND ro.option_id is not NULL;

-- Add version 3 to the history and increment current version
  SET current_version = 3;
  INSERT INTO tlc_tt_version_history (version,description) values (current_version,"Adds Response Tables");
END IF;


-- The following is a template for new versions
--   Copy it and place above this line and remove all leading '-- '
--   Replace all '#' with the next version number

-- --
-- -- VERSION # --
-- --
-- IF current_version < # THEN
-- -- Nothing yet... this is simply a template for adding next version
--
-- -- Add version # to the history and increment current version
--   SET current_version = #;
--   INSERT INTO tlc_tt_version_history (version,description) values (current_version,"<Your Description Here>");
-- END IF;

END//

DELIMITER ;

CALL tlc_tt_upgrade_tables();

DROP PROCEDURE tlc_tt_upgrade_tables;

