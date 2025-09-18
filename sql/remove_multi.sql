ALTER TABLE tlc_tt_survey_questions
CHANGE COLUMN question_type question_type ENUM('INFO', 'BOOL', 'OPTIONS', 'FREETEXT', 'SELECT_MULTI', 'SELECT_ONE') NOT NULL ;

UPDATE tlc_tt_survey_questions set question_type='SELECT_MULTI' where question_type = 'OPTIONS' and multiple=1;
UPDATE tlc_tt_survey_questions set question_type='SELECT_ONE' where question_type = 'OPTIONS' and multiple=0;

ALTER TABLE tlc_tt_survey_questions
DROP COLUMN `multiple`;

ALTER TABLE tlc_tt_survey_questions
CHANGE COLUMN question_type question_type ENUM('INFO', 'BOOL', 'FREETEXT', 'SELECT_MULTI', 'SELECT_ONE') NOT NULL ;

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

CREATE OR REPLACE VIEW tlc_tt_view_question_options AS
SELECT q.survey_id,q.survey_rev,q.question_id, w.str AS wording, qo.sequence, qo.option_id, os.str AS option_str, q.question_type
FROM tlc_tt_survey_questions q
LEFT JOIN tlc_tt_question_options qo on qo.question_id=q.question_id and qo.survey_id=q.survey_id and qo.survey_rev = q.survey_rev
LEFT JOIN tlc_tt_survey_options so on so.survey_id=qo.survey_id and so.survey_rev=qo.survey_rev and so.option_id=qo.option_id
LEFT JOIN tlc_tt_strings w on w.string_id = q.wording_sid
LEFT JOIN tlc_tt_strings os on os.string_id = so.text_sid
WHERE q.question_type like 'SELECT%';
