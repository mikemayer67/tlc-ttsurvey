
table_roots = (
  "access_tokens",
  "reminder_emails",
  "response_options",
  "section_feedback",
  "responses",
  "user_status",
  "settings",
  "roles",
  "reset_tokens",
  "anonids",
  "userids",
  "question_options",
  "question_map",
  "survey_questions",
  "survey_sections",
  "survey_options",
  "surveys",
  "version_history",
)

with open("sql/migration/validate_v_10_to_v_1_1.sql","w") as sql:
    print("drop table if exists migration_verification;",file=sql)
    print("create table migration_verification (table_key varchar(128), ntt int, ntts int);",file=sql)
    print('',file=sql)

    for key in table_roots:
        print(f"insert into migration_verification select '{key}', (select count(*) from tlc_tt_{key}), (select count(*) from tlc_tts_{key});",file=sql)
         