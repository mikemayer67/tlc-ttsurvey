-- IMPORTANT !!!
-- This SQL script is not intended to be run manually.  It supports
--   the database configuration php script which orchestrates the
--   timing and validation of each script that participates in the
--   initialization/modification of the database structure and content.
--
-- If you run this script manually, you are assuming responsibility for
--   any resulting loss of functionality or data.

drop view if exists tlc_srv_draft_surveys;
drop view if exists tlc_srv_active_surveys;
drop view if exists tlc_srv_closed_surveys;
drop view if exists tlc_srv_active_roles;
drop view if exists tlc_srv_view_questions;
drop view if exists tlc_srv_view_question_options;
drop view if exists tlc_srv_view_responses_freetext;
drop view if exists tlc_srv_view_responses_bool;
drop view if exists tlc_srv_view_responses_select_one;
drop view if exists tlc_srv_view_responses_select_multi;
drop view if exists tlc_srv_view_response_options;
drop view if exists tlc_srv_view_last_user_survey;
drop view if exists tlc_srv_view_unused_options;

drop table if exists tlc_srv_response_options_cache;
drop table if exists tlc_srv_responses_cache;
drop table if exists tlc_srv_user_status_cache;
drop table if exists tlc_srv_response_options;
drop table if exists tlc_srv_responses;
drop table if exists tlc_srv_reminder_emails;
drop table if exists tlc_srv_user_status;
drop table if exists tlc_srv_roles;
drop table if exists tlc_srv_reset_tokens;
drop table if exists tlc_srv_access_tokens;
drop table if exists tlc_srv_userids;
drop table if exists tlc_srv_question_options;
drop table if exists tlc_srv_survey_options;
drop table if exists tlc_srv_group_content;
drop table if exists tlc_srv_section_content;
drop table if exists tlc_srv_sections;
drop table if exists tlc_srv_question_groups;
drop table if exists tlc_srv_questions;
drop table if exists tlc_srv_surveys;
drop table if exists tlc_srv_version_history;
drop table if exists tlc_srv_settings;