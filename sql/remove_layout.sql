ALTER TABLE tlc_tt.tlc_tt_survey_questions 
ADD COLUMN question_flags INT NOT NULL DEFAULT 0 AFTER layout;

update tlc_tt_survey_questions set question_flags=1 where layout='RIGHT';
update tlc_tt_survey_questions set question_flags=2 where layout='LCOL';
update tlc_tt_survey_questions set question_flags=3 where layout='RCOL';
update tlc_tt_survey_questions set question_flags=4+question_flags where other_flag = 1;

ALTER TABLE tlc_tt.tlc_tt_survey_questions DROP COLUMN layout;
ALTER TABLE tlc_tt.tlc_tt_survey_questions DROP COLUMN other_flag;

CREATE OR REPLACE VIEW tlc_tt_view_survey_questions AS
SELECT q.question_id, q.survey_id, q.survey_rev, 
  q.wording_sid,     wording.str     AS wording_str,
  q.question_type, 
  CASE WHEN (q.question_flags & 1) > 0 THEN 'RIGHT' ELSE 'LEFT' END AS alignment,
  CASE WHEN (q.question_flags & 2) > 0 THEN 'COL'   ELSE 'ROW'  END AS orientation,
  CASE WHEN q.question_type not like 'SELECT%' THEN NULL
       WHEN (q.question_flags & 4) > 0 THEN 'YES'
       ELSE 'NO'
       END AS has_other,
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
