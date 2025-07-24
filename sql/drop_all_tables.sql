drop view if exists tlc_tt_view_survey_options;
drop view if exists tlc_tt_view_survey_questions;
drop view if exists tlc_tt_view_survey_sections;
drop view if exists tlc_tt_draft_surveys;
drop view if exists tlc_tt_active_surveys;
drop view if exists tlc_tt_closed_surveys;
drop view if exists tlc_tt_user_reset_tokens;
drop view if exists tlc_tt_active_roles;

drop table if exists tlc_tt_settings;
drop table if exists tlc_tt_roles;
drop table if exists tlc_tt_reset_tokens;
drop table if exists tlc_tt_anonids;
drop table if exists tlc_tt_userids;
drop table if exists tlc_tt_question_options;
drop table if exists tlc_tt_question_map;
drop table if exists tlc_tt_survey_questions;
drop table if exists tlc_tt_survey_sections;
drop table if exists tlc_tt_survey_options;
drop table if exists tlc_tt_survey_revisions;
drop table if exists tlc_tt_survey_status;
drop table if exists tlc_tt_strings;
drop table if exists tlc_tt_version_history;

drop procedure if exists tlc_tt_upgrade_tables;
