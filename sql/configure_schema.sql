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

-- version keeps track of the current version throughout the procedure
DECLARE version INT DEFAULT 0;

-- if the version table doesn't yet exist, create it
CREATE TABLE IF NOT EXISTS tlc_tt_version_history (
	version INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description VARCHAR(45) NOT NULL,
  added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);
    
-- see if there are any rows in the table.
-- if not, then we are at version 0 (initial table creation)
SELECT IFNULL(MAX(version),0) INTO version FROM tlc_tt_version_history;

--
-- VERSION 1 --
--
IF version < 1 THEN
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
    question_type ENUM('INFO','BOOL','SELECT_ONE','SELECT_MULTI','FREETEXT') NOT NULL ,
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
  -- There are five Survey question types:
  --    INFO         Not a question, exists to provide info to the survey participants.
  --    BOOL         Yes/No type question (probably will be implemented as a checkbox)
  --    SELECT_ONE   Multiple choice (option) questions, select one
  --    SELECT_MULTI Multiple choice (option) questions, select multiple
  --    FREETEXT     Question where the participant can provide a free form written respone
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
    AS SELECT userid, admin, content, tech
         FROM tlc_tt_roles
        WHERE content=1 OR admin=1 OR tech=1;

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
  SELECT q.survey_id,q.survey_rev,q.question_id, w.str AS wording, qo.sequence, qo.option_id, os.str AS option_str, q.question_type
  FROM tlc_tt_survey_questions q
  LEFT JOIN tlc_tt_question_options qo on qo.question_id=q.question_id and qo.survey_id=q.survey_id and qo.survey_rev = q.survey_rev
  LEFT JOIN tlc_tt_survey_options so on so.survey_id=qo.survey_id and so.survey_rev=qo.survey_rev and so.option_id=qo.option_id
  LEFT JOIN tlc_tt_strings w on w.string_id = q.wording_sid
  LEFT JOIN tlc_tt_strings os on os.string_id = so.text_sid
  WHERE q.question_type like 'SELECT%';


-- Add version 1 to the history and increment current version
  INSERT INTO tlc_tt_version_history (description) values ("Initial Setup");
  SET version = 1;
END IF;

-- The following is a template for new versions
--   Copy it and place above this line and remove all leading '-- '
--   Replace all '#' with the next version number

-- --
-- -- VERSION # --
-- --
-- IF version < # THEN
-- -- Nothing yet... this is simply a template for adding version 2
--
-- -- Add version # to the history and increment current version
--   INSERT INTO tlc_tt_version_history (description) values ("Test Increment");
--   SET version = #;
-- END IF;

END//

DELIMITER ;

CALL tlc_tt_upgrade_tables();

DROP PROCEDURE tlc_tt_upgrade_tables;

