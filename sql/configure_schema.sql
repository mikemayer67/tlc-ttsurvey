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
	version INT AUTO_INCREMENT PRIMARY KEY,
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

  CREATE TABLE tlc_tt_surveys (
    id       int          NOT NULL AUTO_INCREMENT,
    title    varchar(100) NOT NULL,
    created  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    active   datetime     DEFAULT NULL,
    closed   datetime     DEFAULT NULL,
    parent   int          DEFAULT NULL,
    revision int          NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    FOREIGN KEY (parent) REFERENCES tlc_tt_surveys(id) ON UPDATE RESTRICT ON DELETE SET NULL
  );

  CREATE TABLE tlc_tt_survey_options (
    id         int          NOT NULL AUTO_INCREMENT COMMENT 'Provides continuity between surveys',
    survey_id  int          NOT NULL DEFAULT 1,
    survey_rev int          NOT NULL,
    text       varchar(128) NOT NULL COMMENT 'What will appear in the survey form',
    PRIMARY KEY (id,survey_id,survey_rev),
    FOREIGN KEY (survey_id) REFERENCES tlc_tt_surveys(id) ON UPDATE RESTRICT ON DELETE CASCADE
  );

  CREATE TABLE tlc_tt_survey_sections (
    survey_id   int          NOT NULL,
    survey_rev  int          NOT NULL,
    sequence    int          COMMENT 
'Order this section will appear in the survey form.
 NULL excludes the section from the survey form, but retains it for use by descendent surveys.',
    name        varchar(45)  NOT NULL           COMMENT 'Section name that will appear in the survey form',
    description varchar(512) DEFAULT NULL       COMMENT 'Section description that will appear in the survey form',
    feedback    tinyint      NOT NULL DEFAULT 0 COMMENT 'Include a general feedback textarea for this section',
    PRIMARY KEY (survey_id,survey_rev,sequence),
    FOREIGN KEY (survey_id) REFERENCES tlc_tt_surveys(id) ON UPDATE RESTRICT ON DELETE CASCADE
  );

  CREATE TABLE tlc_tt_survey_elements (
    id           int          NOT NULL AUTO_INCREMENT COMMENT 'Provides continuity between surveys',
    survey_id    int          NOT NULL,
    survey_rev   int          NOT NULL,
    section_seq  int          NOT NULL COMMENT 
'0 indicates that this element will be in the header rather than a survey section. 
 Any other value that does not appear in tlc_tt_survey_sections will be excluded from the survey.',
    sequence     int          COMMENT 
'Order this element will appear in the survey section. 
 NULL excludes the element from the survey form, but retains it for use by descendent surveys.',
    label        varchar(128) NOT NULL COMMENT 'The label for this element shown in the survey',
    element_type ENUM('INFO','BOOL','OPTIONS','FREETEXT') NOT NULL COMMENT 
'INFO = Not a question, exists in the flow to provide info to the survey participant. BOOL = Yes/No type question, most likely implemented as a checkbox. OPTIONS = Multiple choice (option) questions. FREETEXT = Question where user can provide a written response.',
    other        varchar(45) DEFAULT NULL COMMENT 
'Only applies if element_type=OPTIONS.
 If not NULL, provides an "other" option, labeled with the value of this field',
    qualifier    varchar(45) DEFAULT NULL COMMENT 
'Only applies if element_type=OPTIONS or BOOL.
 If not NULL, provides a text input field, labeled with the value of this field',
    description  varchar(512) DEFAULT NULL COMMENT 
 'Does not apply if element_type=INFO.
  Text to include in the survey to provide information to the survey participant.',
    info         varchar(1024) DEFAULT NULL COMMENT 
 'If element_type=INFO, provides the information to be displayed (HTML and markdown are acceptable).
  Otherwise, Text to include in a pop-up info box if the participant hovers over the info button.',
    PRIMARY KEY (id,survey_id,survey_rev),
    UNIQUE  KEY (survey_id,survey_rev,section_seq,sequence),
    FOREIGN KEY (survey_id) REFERENCES tlc_tt_surveys(id) ON UPDATE RESTRICT ON DELETE CASCADE
  );

  CREATE TABLE tlc_tt_element_options (
    survey_id   int     NOT NULL,
    survey_rev  int     NOT NULL,
    element_id  int     NOT NULL,
    sequence    int     COMMENT 'If set to NULL, option will not be included',
    option_id   int     NOT NULL,
    secondary   tinyint NOT NULL DEFAULT 0,
    PRIMARY KEY (survey_id,survey_rev,element_id,option_id),
    UNIQUE  KEY (survey_id,survey_rev,element_id,sequence),
    FOREIGN KEY (survey_id) REFERENCES tlc_tt_surveys(id) ON UPDATE RESTRICT ON DELETE CASCADE,
    FOREIGN KEY (survey_id,option_id) REFERENCES tlc_tt_survey_options(survey_id,id)
                ON UPDATE RESTRICT ON DELETE RESTRICT
  );


  CREATE TABLE tlc_tt_userids (
    userid   varchar(24)  PRIMARY KEY,
    fullname varchar(100) NOT NULL,
    email    varchar(45)  DEFAULT NULL,
    token    varchar(45)  NOT NULL COMMENT 'access token',
    password varchar(64)  NOT NULL COMMENT 'hash of the password',
    anonid   varchar(64)  NOT NULL COMMENT 'hash of the anonid or userid',
    admin    tinyint      NOT NULL DEFAULT 0 COMMENT 'has admin permission'
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
    userid    varchar(24) NOT NULL PRIMARY KEY,
    admin     tinyint     NOT NULL DEFAULT 0,
    content   tinyint     NOT NULL DEFAULT 0,
    tech      tinyint     NOT NULL DEFAULT 0,
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
