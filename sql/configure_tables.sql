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

-- If you don't understand the following statement, please read up creating
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
  added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
    
-- see if there are any rows in the table.
-- if not, then we are at version 0 (initial table creation)
SELECT COUNT(*) INTO version FROM tlc_tt_version_history;

-- if we do have a version history get the most recent one
IF version > 0 THEN
	SELECT MAX(version) INTO version FROM tlc_tt_version_history;
END IF;

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
    id       int          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title    varchar(100) NOT NULL,
    created  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    active   DATETIME     NULL DEFAULT NULL,
    closed   DATETIME     NULL DEFAULT NULL,
    download VARCHAR(150) NULL DEFAULT NULL COMMENT 'URL to download a printable survey'
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
    expires   DATETIME         NOT NULL,
    FOREIGN KEY (userid) REFERENCES tlc_tt_userids(userid) ON UPDATE CASCADE ON DELETE CASCADE
  );

  CREATE TABLE tlc_tt_roles (
    userid    varchar(24) NOT NULL PRIMARY KEY,
    admin     tinyint     NOT NULL DEFAULT 0,
    content   tinyint     NOT NULL DEFAULT 0,
    tech      tinyint     NOT NULL DEFAULT 0,
    FOREIGN KEY (userid) REFERENCES tlc_tt_userids(userid) ON UPDATE CASCADE ON DELETE CASCADE
  );

  create table tlc_tt_settings (
    name  varchar(24)  NOT NULL PRIMARY KEY,
    value varchar(255) NOT NULL
  );

  CREATE OR REPLACE VIEW tlc_tt_draft_surveys
    AS SELECT id, title 
         FROM tlc_tt_surveys 
        WHERE active is NULL;

  CREATE OR REPLACE VIEW tlc_tt_active_surveys
    AS SELECT id, title 
         FROM tlc_tt_surveys 
        WHERE active is not NULL and closed is NULL;

  CREATE OR REPLACE VIEW tlc_tt_closed_surveys
    AS SELECT id, title 
         FROM tlc_tt_surveys 
        WHERE closed is not NULL;

  CREATE OR REPLACE VIEW tlc_tt_user_reset_tokens
    AS SELECT u.userid, t.token, t.expires
         FROM tlc_tt_userids u, tlc_tt_reset_tokens t
        WHERE u.userid = t.userid;

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
