-- IMPORTANT !!!
-- This SQL script is not intended to be run manually.  It supports
--   the database configuration php script which orchestrates the
--   timing and validation of each script that participates in the
--   initialization/modification of the database structure and content.
--
-- If you run this script manually, you are assuming responsibility for
--   any resulting loss of functionality or data.

drop view if exists tlc_tt_active_roles;
drop view if exists tlc_tt_active_surveys;
drop view if exists tlc_tt_closed_surveys;
drop view if exists tlc_tt_draft_surveys;
drop view if exists tlc_tt_user_reset_tokens;
drop view if exists tlc_tt_view_last_user_survey;
drop view if exists tlc_tt_view_question_options;
drop view if exists tlc_tt_view_response_options;
drop view if exists tlc_tt_view_responses_bool;
drop view if exists tlc_tt_view_responses_freetext;
drop view if exists tlc_tt_view_responses_select_multi;
drop view if exists tlc_tt_view_responses_select_one;
drop view if exists tlc_tt_view_survey_options;
drop view if exists tlc_tt_view_survey_questions;
drop view if exists tlc_tt_view_survey_sections;
drop view if exists tlc_tt_view_surveys;
drop view if exists tlc_tt_view_unused_options;
drop view if exists tlc_tt_view_unused_strings;

drop table if exists tlc_tt_access_tokens;
drop table if exists tlc_tt_reminder_emails;
drop table if exists tlc_tt_response_options;
drop table if exists tlc_tt_section_feedback;
drop table if exists tlc_tt_responses;
drop table if exists tlc_tt_user_status;
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
drop table if exists tlc_tt_surveys;
drop table if exists tlc_tt_strings;
drop table if exists tlc_tt_version_history;