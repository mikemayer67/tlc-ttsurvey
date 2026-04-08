drop table if exists migration_verification;
create table migration_verification (table_key varchar(128), ntt int, ntts int);

insert into migration_verification select 'access_tokens', (select count(*) from tlc_tt_access_tokens), (select count(*) from tlc_tts_access_tokens);
insert into migration_verification select 'reminder_emails', (select count(*) from tlc_tt_reminder_emails), (select count(*) from tlc_tts_reminder_emails);
insert into migration_verification select 'response_options', (select count(*) from tlc_tt_response_options), (select count(*) from tlc_tts_response_options);
insert into migration_verification select 'responses', (select count(*) from tlc_tt_responses), (select count(*) from tlc_tts_responses);
insert into migration_verification select 'user_status', (select count(*) from tlc_tt_user_status), (select count(*) from tlc_tts_user_status);
insert into migration_verification select 'settings', (select count(*) from tlc_tt_settings), (select count(*) from tlc_tts_settings);
insert into migration_verification select 'roles', (select count(*) from tlc_tt_roles), (select count(*) from tlc_tts_roles);
insert into migration_verification select 'reset_tokens', (select count(*) from tlc_tt_reset_tokens), (select count(*) from tlc_tts_reset_tokens);
insert into migration_verification select 'userids', (select count(*) from tlc_tt_userids), (select count(*) from tlc_tts_userids);
insert into migration_verification select 'question_options', (select count(*) from tlc_tt_question_options), (select count(*) from tlc_tts_question_options);
insert into migration_verification select 'question_map', (select count(*) from tlc_tt_question_map), (select count(*) from tlc_tts_question_map);
insert into migration_verification select 'survey_questions', (select count(*) from tlc_tt_survey_questions), (select count(*) from tlc_tts_survey_questions);
insert into migration_verification select 'survey_sections', (select count(*) from tlc_tt_survey_sections), (select count(*) from tlc_tts_survey_sections);
insert into migration_verification select 'survey_options', (select count(*) from tlc_tt_survey_options), (select count(*) from tlc_tts_survey_options);
insert into migration_verification select 'surveys', (select count(*) from tlc_tt_surveys), (select count(*) from tlc_tts_surveys);
insert into migration_verification select 'version_history', (select count(*) from tlc_tt_version_history), (select count(*) from tlc_tts_version_history);
