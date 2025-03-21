drop table if exists tlc_tt_roles;
drop table if exists tlc_tt_reset_tokens;
drop table if exists tlc_tt_anonids;
drop table if exists tlc_tt_userids;
drop table if exists tlc_tt_surveys;
drop table if exists tlc_tt_version_history;

drop view if exists tlc_tt_active_surveys;
drop view if exists tlc_tt_closed_surveys;
drop view if exists tlc_tt_draft_surveys;
drop view if exists tlc_tt_user_reset_tokens;

drop procedure if exists tlc_tt_upgrade_tables;
