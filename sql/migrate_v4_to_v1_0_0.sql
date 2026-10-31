ALTER TABLE tlc_tt_userids DROP COLUMN token;

DELETE FROM tlc_tt_version_history;

ALTER TABLE `tlc_tt`.`tlc_tt_version_history` 
CHANGE COLUMN `version` `version` VARCHAR(32) NOT NULL ,
CHANGE COLUMN `description` `change_description` VARCHAR(512) NOT NULL ;

INSERT INTO tlc_tt_version_history (`version`, `change_description`) VALUES ('1.0.0', 'Initial Database Version');

DROP VIEW tlc_tt_view_survey_questions;

CREATE VIEW tlc_tt_view_survey_questions AS
SELECT q.question_id, q.survey_id, 
   q.wording_sid,     wording.str     AS wording_str,
   q.question_type, 
   CASE WHEN (q.question_flags & 0x01) > 0 THEN 'RIGHT'  ELSE 'LEFT' END AS alignment,
   CASE WHEN (q.question_flags & 0x02) > 0 THEN 'COLUMN' ELSE 'ROW'  END AS orientation,
   CASE WHEN (q.question_flags & 0x08) > 0 THEN 'YES' 
        WHEN (q.question_flags & 0x10) > 0 THEN 'NEW'
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
