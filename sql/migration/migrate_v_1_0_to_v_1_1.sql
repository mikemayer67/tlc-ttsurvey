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

--- The survey table must be updated to use actual title strings
---   rather than string IDs
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

--- The survey options table must be updated to use actual option string
---  rather than a string ID
CREATE TABLE tlc_tts_survey_options (
  survey_id  smallint UNSIGNED NOT NULL,
  option_id  smallint UNSIGNED NOT NULL COMMENT 'Provides continuity between surveys',
  option_str varchar(128)      NOT NULL COMMENT 'What will appear in the survey form',
  PRIMARY KEY (survey_id,option_id),
  FOREIGN KEY (survey_id) REFERENCES tlc_tts_surveys(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
);
INSERT into tlc_tts_survey_options (survey_id, option_id, option_str)
SELECT t.survey_id, t.option_id, s.str
  FROM tlc_tt_survey_options t JOIN tlc_tt_strings s on s.string_id = t.text_sid;

--- The survey sections table must be updated to use actual name, intro, and feedback strings
---   rather than string IDs
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
  FOREIGN KEY (survey_id) REFERENCES tlc_tts_surveys(survey_id) ON UPDATE RESTRICT ON DELETE CASCADE
);
INSERT into tlc_tts_survey_sections (survey_id, section_id, sequence, name, collapsible, intro, feedback)
  SELECT t.survey_id, t.section_id, t.sequence, sn.str, t.collapsible, si.str, sf.str
    FROM tlc_tt_survey_sections t
    LEFT JOIN tlc_tt_strings sn on sn.string_id = t.name_sid
    LEFT JOIN tlc_tt_strings si on si.string_id = t.intro_sid
    LEFT JOIN tlc_tt_strings sf on sf.string_id = t.feedback_sid;
  

--- The survey questions table must be updated to use actual wording, other, qualifier, intro, and info
---   rather than string IDs
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
INSERT into tlc_tts_survey_questions 
  ( question_id, survey_id, wording, question_type, question_flags, other, qualifier, intro, info )
  SELECT t.question_id, t.survey_id, sw.str, t.question_type, t.question_flags, so.str, sq.str, si.str, sp.str
    FROM tlc_tt_survey_questions t
    LEFT JOIN tlc_tt_strings sw on sw.string_id = t.wording_sid
    LEFT JOIN tlc_tt_strings so on so.string_id = t.other_sid
    LEFT JOIN tlc_tt_strings sq on sq.string_id = t.qualifier_sid
    LEFT JOIN tlc_tt_strings si on si.string_id = t.intro_sid
    LEFT JOIN tlc_tt_strings sp on sp.string_id = t.info_sid;

--- No change to the question map table other than prefix
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
INSERT into tlc_tts_question_map (survey_id, section_id, question_seq, question_id)
SELECT survey_id, section_id, question_seq, question_id
  FROM tlc_tt_question_map;

--- No change to the question options table other than prefix
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
INSERT into tlc_tts_question_options (survey_id, question_id, sequence, option_id)
SELECT survey_id, question_id, sequence, option_id
  FROM tlc_tt_question_options;