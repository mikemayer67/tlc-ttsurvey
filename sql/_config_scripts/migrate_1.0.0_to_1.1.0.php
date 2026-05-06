<?php

if(!defined('IN_MIGRATION')) { 
  throw new Exception(
    __FILE__ . " cannot be run directly.\n".
    "It must be included by configure_database.php"
  );
}

// Simply define an (ordered) array of SQL commands

return [

// Copy over existing data from v1.0 (tlc_tt_) tables to v1.1 (tlc_srv_) tables.
// This includes removing the string ID table and migrating data accordingly.

// Copy existing settings
  [ __LINE__, <<<SQL
INSERT into tlc_srv_settings (name, value)
SELECT name, value from tlc_tt_settings;
SQL ],

// Copy existing version history.  New migration entry will be added later
  [ __LINE__, <<<SQL
INSERT INTO tlc_srv_version_history (version, change_description, added)
SELECT version, description, added FROM tlc_tt_version_history;
SQL ],

// The survey table must be updated to use actual title strings rather than string IDs
  [ __LINE__, <<<SQL
INSERT into tlc_srv_surveys (survey_id, parent_id, title, created, modified, active, closed)
SELECT t.survey_id, t.parent_id, s.str, t.created, t.modified, t.active, t.closed
  FROM tlc_tt_surveys t JOIN tlc_tt_strings s on s.string_id = t.title_sid;
SQL ],

// The survey questions table must be updated to use actual wording, other, qualifier, intro, and info
//   rather than string IDs
// The grouping of question is now handled by the survey content map and no longer by the question_flags.
//   But we do want to retain the "show info as grouped" flag. 
//   So... strip the bit masked by 0x08 (i.e. keep the bits masked by 0x17).
  [ __LINE__, <<<SQL
INSERT into tlc_srv_questions 
  ( question_id, survey_id, wording, question_type, question_flags, other, qualifier, intro, info )
  SELECT t.question_id, 
         t.survey_id, 
         sw.str, 
         t.question_type, 
         t.question_flags & 0x17,
         so.str, 
         sq.str, 
         si.str, 
         sp.str
    FROM tlc_tt_survey_questions t
    LEFT JOIN tlc_tt_strings sw on sw.string_id = t.wording_sid
    LEFT JOIN tlc_tt_strings so on so.string_id = t.other_sid
    LEFT JOIN tlc_tt_strings sq on sq.string_id = t.qualifier_sid
    LEFT JOIN tlc_tt_strings si on si.string_id = t.intro_sid
    LEFT JOIN tlc_tt_strings sp on sp.string_id = t.info_sid;
SQL ],

// Not migrating question_groups using SQL.  See the php migration script for this

// The survey sections table must be updated to use actual name, and intro strings
//   rather than string IDs
  [ __LINE__, <<<SQL
INSERT into tlc_srv_sections (survey_id, section_id, sequence, name, collapsible, intro)
  SELECT t.survey_id, t.section_id, t.sequence, sn.str, t.collapsible, si.str
    FROM tlc_tt_survey_sections t
    LEFT JOIN tlc_tt_strings sn on sn.string_id = t.name_sid
    LEFT JOIN tlc_tt_strings si on si.string_id = t.intro_sid;
SQL ],
  
// Both the section content and group content tables will be populated using the
//   php migration script.

// The survey options table must be updated to use actual option string
//  rather than a string ID
  [ __LINE__, <<<SQL
INSERT into tlc_srv_survey_options (survey_id, option_id, option_str)
SELECT t.survey_id, t.option_id, s.str
  FROM tlc_tt_survey_options t JOIN tlc_tt_strings s on s.string_id = t.text_sid;
SQL ],

// No change to the question options table other than prefix
  [ __LINE__, <<<SQL
INSERT into tlc_srv_question_options (survey_id, question_id, sequence, option_id)
SELECT survey_id, question_id, sequence, option_id FROM tlc_tt_question_options;
SQL ],

// No change to the userid table other than prefix
  [ __LINE__, <<<SQL
INSERT into tlc_srv_userids (userid, fullname, email, password, admin)
SELECT userid, fullname, email, password, admin FROM tlc_tt_userids;
SQL ],

// No change to the access token table other than prefix
  [ __LINE__, <<<SQL
INSERT into tlc_srv_access_tokens (userid, token, expires)
SELECT userid, token, expires FROM tlc_tt_access_tokens;
SQL ],

// No change to the reset tokrens table other than prefix
  [ __LINE__, <<<SQL
INSERT into tlc_srv_reset_tokens (userid, token, expires)
SELECT userid, token, expires FROM tlc_tt_reset_tokens;
SQL ],

// No change to the user roles table other than prefix
  [ __LINE__, <<<SQL
INSERT into tlc_srv_roles (userid, admin, content, tech, summary)
SELECT userid, admin, content, tech, summary FROM tlc_tt_roles;
SQL ],

// No change to the user status table other than prefix
  [ __LINE__, <<<SQL
INSERT into tlc_srv_user_status (userid, survey_id, draft, submitted, email_sent, sent_to)
SELECT userid, survey_id, draft, submitted, email_sent, sent_to FROM tlc_tt_user_status;
SQL ],

// No change to the reminder email table other than prefix
  [ __LINE__, <<<SQL
INSERT into tlc_srv_reminder_emails (userid, subject, last_sent, email)
SELECT userid, subject, last_sent, email FROM tlc_tt_reminder_emails;
SQL ],

// No change to the user response table other than prefix
  [ __LINE__, <<<SQL
INSERT into tlc_srv_responses (userid, survey_id, question_id, draft, selected, free_text, qualifier, other)
SELECT userid, survey_id, question_id, draft, selected, free_text, qualifier, other from tlc_tt_responses;
SQL ],

// No change to the response options table other than prefix
  [ __LINE__, <<<SQL
INSERT into tlc_srv_response_options (userid, survey_id, question_id, draft, option_id)
SELECT userid, survey_id, question_id, draft, option_id from tlc_tt_response_options;
SQL ],

];