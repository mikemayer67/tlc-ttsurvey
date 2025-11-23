insert into tlc_tt_strings (string_id,str) select string_id,str from old_tt_strings;

insert into tlc_tt_surveys
  select ss.survey_id, ss.parent_id, sr.title_sid, ss.created, sr.modified, ss.active, ss.closed
    from old_tt_survey_status ss 
    left join old_tt_survey_revisions sr on sr.survey_id=ss.survey_id
; 

insert into tlc_tt_survey_options 
  select survey_id, option_id, text_sid from old_tt_survey_options;

insert into tlc_tt_survey_sections 
  select survey_id, sequence, name_sid,collapsible,intro_sid,feedback_sid from old_tt_survey_sections;

insert into tlc_tt_survey_questions
  select question_id, survey_id, wording_sid, question_type, question_flags, other_sid, qualifier_sid, intro_sid, info_sid from old_tt_survey_questions;

insert into tlc_tt_question_map
  select survey_id, section_seq, question_seq,question_id from old_tt_question_map;

insert into tlc_tt_question_options
  select survey_id, question_id, sequence, option_id from old_tt_question_options;

insert into tlc_tt_userids select * from old_tt_userids;

insert into tlc_tt_roles select * from old_tt_roles;

insert into tlc_tt_settings select * from old_tt_settings;

insert into tlc_tt_user_status select * from old_tt_user_status;

insert into tlc_tt_responses select * from old_tt_responses;

insert into tlc_tt_section_feedback select * from old_tt_section_feedback;

insert into tlc_tt_response_options select * from old_tt_response_options;


