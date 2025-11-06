delete from tlc_tt_response_options;
delete from tlc_tt_responses;
delete from tlc_tt_user_status;

INSERT INTO tlc_tt_user_status (userid,survey_id,draft,submitted) values ('mikemayer67',2,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP);

INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,free_text) 
       SELECT 'mikemayer67',2,question_id,0,concat('I like ',wording_str) from tlc_tt_view_survey_questions where survey_id=2 and survey_rev=2 and question_type='FREETEXT' and question_id%2=1;

INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,free_text) 
       SELECT 'mikemayer67',2,question_id,1,concat('I might like ',wording_str) from tlc_tt_view_survey_questions where survey_id=2 and survey_rev=2 and question_type='FREETEXT' and question_id%2=1;


INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,selected,qualifier) 
       SELECT 'mikemayer67',2,question_id,0,question_id%2, case when question_id%3=0 then 'Yep' else NULL end from tlc_tt_view_survey_questions where survey_id=2 and survey_rev=2 and question_type='BOOL';

INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,selected,qualifier) 
       SELECT 'mikemayer67',2,question_id,1,1,'I need to think about it' from tlc_tt_view_survey_questions where survey_id=2 and survey_rev=2 and question_type='BOOL';


INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,selected,other) 
       SELECT 'mikemayer67',2,question_id,0,0,'MyChoice' from tlc_tt_view_survey_questions where survey_id=2 and survey_rev=2 and question_type='SELECT_ONE' and question_id%4=0;

INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,selected,other) 
       SELECT 'mikemayer67',2,question_id,1,0,'Hmmm...' from tlc_tt_view_survey_questions where survey_id=2 and survey_rev=2 and question_type='SELECT_ONE' and question_id%4=0;

INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,selected) 
       SELECT 'mikemayer67',2,q.question_id,0,min(qo.option_id)
         FROM tlc_tt_view_survey_questions q
         LEFT JOIN tlc_tt_view_question_options qo ON qo.survey_id=q.survey_id and qo.survey_rev=q.survey_rev and qo.question_id=q.question_id
        WHERE q.survey_id=2 and q.survey_rev=2 and q.question_type='SELECT_ONE' and q.question_id%4=1
        group by q.question_id;

INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,selected,qualifier) 
       SELECT 'mikemayer67',2,q.question_id,1,min(qo.option_id),'Thinking'
         FROM tlc_tt_view_survey_questions q
         LEFT JOIN tlc_tt_view_question_options qo ON qo.survey_id=q.survey_id and qo.survey_rev=q.survey_rev and qo.question_id=q.question_id
        WHERE q.survey_id=2 and q.survey_rev=2 and q.question_type='SELECT_ONE' and q.question_id%4=1
        group by q.question_id;

INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,selected,qualifier) 
       SELECT 'mikemayer67',2,q.question_id,0,max(qo.option_id),'Love it'
         FROM tlc_tt_view_survey_questions q
         LEFT JOIN tlc_tt_view_question_options qo ON qo.survey_id=q.survey_id and qo.survey_rev=q.survey_rev and qo.question_id=q.question_id
        WHERE q.survey_id=2 and q.survey_rev=2 and q.question_type='SELECT_ONE' and q.question_id%4=2
        group by q.question_id;

INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,selected,qualifier) 
       SELECT 'mikemayer67',2,q.question_id,1,max(qo.option_id),'Thinking'
         FROM tlc_tt_view_survey_questions q
         LEFT JOIN tlc_tt_view_question_options qo ON qo.survey_id=q.survey_id and qo.survey_rev=q.survey_rev and qo.question_id=q.question_id
        WHERE q.survey_id=2 and q.survey_rev=2 and q.question_type='SELECT_ONE' and q.question_id%4=2
        group by q.question_id;

INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,selected,other) 
       SELECT 'mikemayer67',2,question_id,0,0,'MyChoice' from tlc_tt_view_survey_questions where survey_id=2 and survey_rev=2 and question_type='SELECT_MULTI' and question_id%4=0;

INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,selected,other) 
       SELECT 'mikemayer67',2,question_id,1,0,'Hmmm...' from tlc_tt_view_survey_questions where survey_id=2 and survey_rev=2 and question_type='SELECT_MULTI' and question_id%4=0;

INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft,qualifier) 
       SELECT 'mikemayer67',2,question_id,0,'Sounds good' from tlc_tt_view_survey_questions where survey_id=2 and survey_rev=2 and question_type='SELECT_MULTI' and question_id%4=1;

INSERT INTO tlc_tt_response_options (userid,survey_id,question_id,draft,option_id)
       SELECT 'mikemayer67',2,q.question_id,0, qo.option_id
         FROM tlc_tt_view_survey_questions q
         LEFT JOIN tlc_tt_view_question_options qo on qo.survey_id=q.survey_id and qo.survey_rev=q.survey_rev and qo.question_id=q.question_id
        WHERE q.survey_id=2 and q.survey_rev=2 and q.question_type='SELECT_MULTI' and q.question_id%4=1;

INSERT INTO tlc_tt_responses (userid,survey_id,question_id,draft) 
       SELECT 'mikemayer67',2,question_id,1 from tlc_tt_view_survey_questions where survey_id=2 and survey_rev=2 and question_type='SELECT_MULTI' and question_id%4=1;

INSERT INTO tlc_tt_response_options (userid,survey_id,question_id,draft,option_id)
       SELECT 'mikemayer67',2,q.question_id,1, qo.option_id
         FROM tlc_tt_view_survey_questions q
         LEFT JOIN tlc_tt_view_question_options qo on qo.survey_id=q.survey_id and qo.survey_rev=q.survey_rev and qo.question_id=q.question_id
        WHERE q.survey_id=2 and q.survey_rev=2 and q.question_type='SELECT_MULTI' and q.question_id%4=1;



