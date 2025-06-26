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
    id       smallint      UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    str      varchar(1024) NOT NULL,
    str_hash binary(32)    GENERATED ALWAYS AS (UNHEX(SHA2(str, 256))) STORED,
    UNIQUE KEY (str_hash)
  );

  CREATE TABLE tlc_tt_surveys (
    id       smallint     UNSIGNED NOT NULL AUTO_INCREMENT,
    title    varchar(100) NOT NULL,
    created  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    active   datetime     DEFAULT NULL,
    closed   datetime     DEFAULT NULL,
    parent   smallint     UNSIGNED DEFAULT NULL,
    revision smallint     UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    FOREIGN KEY (parent) REFERENCES tlc_tt_surveys(id) ON UPDATE RESTRICT ON DELETE SET NULL
  );

  CREATE TABLE tlc_tt_survey_options (
    survey_id  smallint UNSIGNED NOT NULL,
    id         smallint UNSIGNED NOT NULL COMMENT 'Provides continuity between surveys',
    survey_rev smallint UNSIGNED NOT NULL,
    text       smallint UNSIGNED NOT NULL COMMENT '(StringID) What will appear in the survey form',
    PRIMARY KEY (id,survey_id,survey_rev),
    FOREIGN KEY (survey_id) REFERENCES tlc_tt_surveys(id) ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (text)      REFERENCES tlc_tt_strings(id) ON UPDATE RESTRICT ON DELETE RESTRICT
  );

  CREATE TABLE tlc_tt_survey_sections (
    survey_id   smallint UNSIGNED NOT NULL,
    survey_rev  smallint UNSIGNED NOT NULL,
    sequence    smallint UNSIGNED NOT NULL         COMMENT 'Order this section will appear in the survey form.',
    name        smallint UNSIGNED                  COMMENT '(StringID) Section name that will appear in the editor and on survey tabs. NULL excludes this section from the survey',
    show_name   tinyint  UNSIGNED DEFAULT NULL COMMENT 'Whether to include the name as a section header',
    description smallint UNSIGNED DEFAULT NULL COMMENT '(StringID) Section description that will appear in the survey form',
    feedback    smallint UNSIGNED DEFAULT NULL COMMENT '(StringID) Text used to prompt for feedback. No feedback allowed if NULL',
    PRIMARY KEY (survey_id,survey_rev,sequence),
    FOREIGN KEY (survey_id)   REFERENCES tlc_tt_surveys(id) ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (name)        REFERENCES tlc_tt_strings(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (description) REFERENCES tlc_tt_strings(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (feedback)    REFERENCES tlc_tt_strings(id) ON UPDATE RESTRICT ON DELETE RESTRICT
  );
  -- Notes:
  --  The survey rev indicates the first revision for which this setting applies.  It will apply to later 
  --    revisions as well, provided there is not a newer survey_rev entry.
  --  To avoid potential ordering conflicts, any time there is a change to list of sections or their order
  --    in the survey, all section entries must be updated with a new survey_rev value.
  --  Setting the section to NULL excludes the section from the survey beginning with the corresponding survey rev.
  --    (This can be subsquenetly reversed in a later rev.)


  CREATE TABLE tlc_tt_survey_questions (
    id            smallint UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Provides continuity between surveys',
    survey_id     smallint UNSIGNED NOT NULL,
    survey_rev    smallint UNSIGNED NOT NULL,
    section       smallint UNSIGNED                    COMMENT 'Which survey section the question is included in.',
    sequence      smallint UNSIGNED                    COMMENT 'Order this question will appear in the survey section.  (NULL excludes the question).',
    wording       smallint UNSIGNED NOT NULL           COMMENT '(StringID) The wording of this question shown in the survey',
    question_type ENUM('INFO','BOOL','OPTIONS','FREETEXT') NOT NULL ,
    multiple      tinyint     DEFAULT NULL             COMMENT 'For OPTIONS type, multiple options can be selected',
    other         smallint UNSIGNED DEFAULT NULL       COMMENT '(StringID) For OPTIONS type, provide an "other" option with the specified label',
    qualifier     smallint UNSIGNED DEFAULT NULL       COMMENT '(StringID) For OPTIONS/BOOL types, provide a text input field with the specified label',
    description   smallint UNSIGNED DEFAULT NULL       COMMENT '(StringID) For non-INFO types, provides a description of the question on the survey',
    info          smallint UNSIGNED DEFAULT NULL       COMMENT '(StringID) Additional information about the question. For INFO, will appear on the form.  For all others, will appear in pop-ups.',
    PRIMARY KEY (id,survey_id,survey_rev),
    UNIQUE  KEY (survey_id,survey_rev,section,sequence),
    FOREIGN KEY (survey_id)   REFERENCES tlc_tt_surveys(id) ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (wording)     REFERENCES tlc_tt_strings(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (other)       REFERENCES tlc_tt_strings(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (qualifier)   REFERENCES tlc_tt_strings(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (description) REFERENCES tlc_tt_strings(id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    FOREIGN KEY (info)        REFERENCES tlc_tt_strings(id) ON UPDATE RESTRICT ON DELETE RESTRICT
  );
  -- Notes:
  --  The survey rev indicates the first revision for which this setting applies.  It will apply to later 
  --    revisions as well, provided there is not a newer survey_rev entry.
  -- If the section is not found in tlc_tt_survey_sections, the question will not be included in the survey.
  -- If the sequence is set to NULL, the question will not be included in the survey.  It is, however, retained in the
  --    databse to establish continuity between ancestor/descendant surveys even if the question is not included
  --    in the current survey.  (This is useful for recurring surveys where a given question may not always apply).
  -- Survey types:
  --    INFO      Not a question, exists to provide info to the survey participants.
  --    BOOL      Yes/No type question (probably will be implemented as a checkbox)
  --    OPTIONS   Multiple choice (option) questions.
  --    FREETEXT  Question where the participant can provide a free form written respone
  -- The info column can contain HTML and Markdown.


  CREATE TABLE tlc_tt_question_options (
    survey_id   smallint UNSIGNED NOT NULL,
    survey_rev  smallint UNSIGNED NOT NULL,
    question_id smallint UNSIGNED NOT NULL,
    sequence    smallint UNSIGNED COMMENT 'If set to NULL, option will not be included',
    option_id   smallint UNSIGNED NOT NULL,
    secondary   tinyint  UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (survey_id,survey_rev,question_id,option_id),
    UNIQUE  KEY (survey_id,survey_rev,question_id,sequence),
    FOREIGN KEY (survey_id) REFERENCES tlc_tt_surveys(id) ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (survey_id,option_id) 
                REFERENCES tlc_tt_survey_options(survey_id,id)
                ON UPDATE RESTRICT ON DELETE RESTRICT
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

  CREATE OR REPLACE VIEW tlc_tt_draft_surveys
    AS SELECT *
         FROM tlc_tt_surveys 
        WHERE active is NULL and closed is NULL;

  CREATE OR REPLACE VIEW tlc_tt_active_surveys
    AS SELECT *
         FROM tlc_tt_surveys 
        WHERE active is not NULL and closed is NULL;

  CREATE OR REPLACE VIEW tlc_tt_closed_surveys
    AS SELECT *
         FROM tlc_tt_surveys 
        WHERE closed is not NULL;

  CREATE OR REPLACE VIEW tlc_tt_user_reset_tokens
    AS SELECT u.userid, t.token, t.expires
         FROM tlc_tt_userids u, tlc_tt_reset_tokens t
        WHERE u.userid = t.userid;
        
  CREATE OR REPLACE VIEW tlc_tt_active_roles
    AS SELECT userid, admin, content, tech
         FROM tlc_tt_roles
        WHERE userid=1 OR admin=1 OR tech=1;

  CREATE OR REPLACE VIEW tlc_tt_view_survey_sections AS
  SELECT s.survey_id, s.survey_rev, s.sequence,
    s.name,        name.str         AS name_str,
    s.show_name,
    s.description, description.str  AS description_str,
    s.feedback,    feedback.str     AS feedback_str
  FROM tlc_tt_survey_sections s
  LEFT JOIN tlc_tt_strings name        ON s.name = name.id
  LEFT JOIN tlc_tt_strings description ON s.description = description.id
  LEFT JOIN tlc_tt_strings feedback    ON s.feedback = feedback.id;

  CREATE OR REPLACE VIEW tlc_tt_view_survey_questions AS
  SELECT q.id, q.survey_id, q.survey_rev, q.section, q.sequence,
    q.wording,     wording.str      AS wording_str,
    q.question_type, 
    q.multiple,
    q.other,       other.str        AS other_str,
    q.qualifier,   qualifier.str    AS qualifier_str,
    q.description, description.str  AS description_str,
    q.info,        info.str         AS info_str
  FROM tlc_tt_survey_questions q
  LEFT JOIN tlc_tt_strings wording     ON q.wording = wording.id
  LEFT JOIN tlc_tt_strings other       ON q.other = other.id
  LEFT JOIN tlc_tt_strings qualifier   ON q.qualifier = qualifier.id
  LEFT JOIN tlc_tt_strings description ON q.description = description.id
  LEFT JOIN tlc_tt_strings info        ON q.info = info.id;

  CREATE OR REPLACE VIEW tlc_tt_view_survey_options AS
  SELECT o.survey_id, o.id, o.survey_rev, o.text, text.str AS text_str
  FROM tlc_tt_survey_options o
  LEFT JOIN tlc_tt_strings text ON o.text = text.id;


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
