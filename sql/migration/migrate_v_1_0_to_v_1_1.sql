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
SELECT survey_id, section_id, question_seq, question_id FROM tlc_tt_question_map;

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
SELECT survey_id, question_id, sequence, option_id FROM tlc_tt_question_options;

--- No change to the question options table other than prefix
CREATE TABLE tlc_tts_userids (
  userid   varchar(24)  PRIMARY KEY,
  fullname varchar(100) NOT NULL,
  email    varchar(45)  DEFAULT NULL,
  password varchar(64)  NOT NULL COMMENT 'hash of the password',
  anonid   varchar(64)  NOT NULL COMMENT 'hash of the anonid or userid',
  admin    tinyint      UNSIGNED NOT NULL DEFAULT 0 COMMENT 'has admin permission'
);
INSERT into tlc_tts_userids (userid, fullname, email, password, anonid, admin)
SELECT userid, fullname, email, password, anonid, admin FROM tlc_tt_userids;

--- No change to the question options table other than prefix
CREATE TABLE tlc_tts_anonids (
  anonid    varchar(24) UNIQUE
);
INSERT into tlc_tts_anonids (anonid)
SELECT anonid FROM tlc_tt_anonids;

--- No change to the reset tokrens table other than prefix
CREATE TABLE tlc_tts_reset_tokens (
  userid    varchar(24)      NOT NULL PRIMARY KEY,
  token     varchar(20)      NOT NULL,
  expires   datetime         NOT NULL,
  FOREIGN KEY (userid) REFERENCES tlc_tts_userids(userid) ON UPDATE RESTRICT ON DELETE CASCADE
);
INSERT into tlc_tts_reset_tokens (userid, token, expires)
SELECT userid, token, expires FROM tlc_tt_reset_tokens;

--- No change to the user roles table other than prefix
CREATE TABLE tlc_tts_roles (
  userid    varchar(24)         NOT NULL PRIMARY KEY,
  admin     tinyint     UNSIGNED NOT NULL DEFAULT 0,
  content   tinyint     UNSIGNED NOT NULL DEFAULT 0,
  tech      tinyint     UNSIGNED NOT NULL DEFAULT 0,
  summary   tinyint     UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (userid) REFERENCES tlc_tts_userids(userid) ON UPDATE RESTRICT ON DELETE CASCADE
);
INSERT into tlc_tts_roles (userid, admin, content, tech, summary)
SELECT userid, admin, content, tech, summary FROM tlc_tt_roles;

--- No change to the settings table other than prefix
create table tlc_tts_settings (
  name  varchar(24)  NOT NULL PRIMARY KEY,
  value varchar(255) NOT NULL
);
INSERT into tlc_tts_settings (name, value)
SELECT name, value from tlc_tt_settings;

--- No change to the user status table other than prefix
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
INSERT into tlc_tts_user_status (userid, survey_id, draft, submitted, email_sent, sent_to)
SELECT userid, survey_id, draft, submitted, email_sent, sent_to FROM tlc_tt_user_status;

--- No change to the user response table other than prefix
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
INSERT into tlc_tts_responses (userid, survey_id, question_id, draft, selected, free_text, qualifier, other)
SELECT userid, survey_id, question_id, draft, selected, free_text, qualifier, other from tlc_tt_responses;

--- No change to the section feedback table other than prefix
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
INSERT into tlc_tts_section_feedback (userid, survey_id, section_id, draft, feedback)
SELECT userid, survey_id, section_id, draft, feedback from tlc_tt_section_feedback;

--- No change to the response options table other than prefix
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
INSERT into tlc_tts_response_options (userid, survey_id, question_id, draft, option_id)
SELECT userid, survey_id, question_id, draft, option_id from tlc_tt_response_options;

--- No change to the reminder email table other than prefix
CREATE TABLE tlc_tts_reminder_emails (
  userid    varchar(24) NOT NULL,
  subject   varchar(32) NOT NULL,
  last_sent datetime    NOT NULL,
  email     varchar(45) NOT NULL,
  PRIMARY KEY (userid),
  FOREIGN KEY (userid) REFERENCES tlc_tts_userids(userid) on UPDATE RESTRICT ON DELETE CASCADE
);
INSERT into tlc_tts_reminder_emails (userid, subject, last_sent, email)
SELECT userid, subject, last_sent, email FROM tlc_tt_reminder_emails;

--- No change to the access token table other than prefix
CREATE TABLE tlc_tts_access_tokens (
  userid   varchar(24)  NOT NULL,
  token    varchar(45)  NOT NULL COMMENT 'access token',
  expires  datetime     NOT NULL COMMENT 'when the token expires unless renewed',
  PRIMARY KEY (userid,token),
  FOREIGN KEY (userid) REFERENCES tlc_tts_userids(userid) on UPDATE RESTRICT ON DELETE CASCADE
);
INSERT into tlc_tts_access_tokens (userid, token, expires)
SELECT userid, token, expires FROM tlc_tt_access_tokens;


--- The following views all just need to have their prefix updated

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
VALUES ('1.1.0', 'Migrated Database from version 1.0');