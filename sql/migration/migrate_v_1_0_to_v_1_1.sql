--- Copy over existing data from v1.0 (tlc_tt_) tables to v1.1 (tlc_tts_) tables.
--- This includes removing the string ID table and migrating data accordingly.

--- No change to the version history table other than prefix
CREATE TABLE tlc_tts_version_history (
  version VARCHAR(32) PRIMARY KEY,
  change_description VARCHAR(512) NOT NULL,
  added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);
INSERT into tlc_tts_version_history 
SELECT * from tlc_tt_version_history;

--- Only change to the survey table is to replace title_sid (a string ID)
---  with the actual title string from the old string table
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
INSERT into tlc_tts_surveys (survey_id, parent_id, title, created, modified, active, closed)
SELECT t.survey_id, t.parent_id, s.str, t.created, t.modified, t.active, t.closed
  FROM tlc_tt_surveys t JOIN tlc_tt_strings s on s.string_id = t.title_sid;