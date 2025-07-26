DELETE FROM tlc_tt_question_options;
DELETE FROM tlc_tt_question_map;
DELETE FROM tlc_tt_survey_questions;
DELETE FROM tlc_tt_survey_sections;
DELETE FROM tlc_tt_survey_options;
DELETE FROM tlc_tt_survey_status;
DELETE FROM tlc_tt_survey_revisions;
DELETE FROM tlc_tt_roles;
DELETE FROM tlc_tt_userids;
DELETE FROM tlc_tt_settings;

DELETE FROM tlc_tt_strings;
ALTER TABLE tlc_tt_strings AUTO_INCREMENT = 1;

INSERT INTO tlc_tt_strings (str) values('Accompany');
INSERT INTO tlc_tt_strings (str) values('Admin');
INSERT INTO tlc_tt_strings (str) values('Admins have ability to log into our busieness account, Editors can post as Trinity. Subscribers recevie posts in their feed and can post as themselves on our page.');
INSERT INTO tlc_tt_strings (str) values('Alto');
INSERT INTO tlc_tt_strings (str) values('Anything we should know?');
INSERT INTO tlc_tt_strings (str) values('Anything:');
INSERT INTO tlc_tt_strings (str) values('Availability');
INSERT INTO tlc_tt_strings (str) values('Backend');
INSERT INTO tlc_tt_strings (str) values('Bass');
INSERT INTO tlc_tt_strings (str) values('Bells');
INSERT INTO tlc_tt_strings (str) values('Blah blah blah... This is important because');
INSERT INTO tlc_tt_strings (str) values('But why is the name partiall y in Frenche?  That is a might fine question for which I do not have an answer.  Ok, reasonable, .... but what are we even talking about at this point?');
INSERT INTO tlc_tt_strings (str) values('Choir');
INSERT INTO tlc_tt_strings (str) values('Communications');
INSERT INTO tlc_tt_strings (str) values('Content');
INSERT INTO tlc_tt_strings (str) values('Coordinate');
INSERT INTO tlc_tt_strings (str) values('Direct');
INSERT INTO tlc_tt_strings (str) values('Do you prefer piano or organ during worship?');
INSERT INTO tlc_tt_strings (str) values('Drive');
INSERT INTO tlc_tt_strings (str) values('Editor');
INSERT INTO tlc_tt_strings (str) values('Experience');
INSERT INTO tlc_tt_strings (str) values('Facebook');
INSERT INTO tlc_tt_strings (str) values('Hello');
INSERT INTO tlc_tt_strings (str) values('Help set up/tear down worship mics');
INSERT INTO tlc_tt_strings (str) values('Help with Sound');
INSERT INTO tlc_tt_strings (str) values('How would you like to help music');
INSERT INTO tlc_tt_strings (str) values('Info Text');
INSERT INTO tlc_tt_strings (str) values('Instagram');
INSERT INTO tlc_tt_strings (str) values('Instrument (s)');
INSERT INTO tlc_tt_strings (str) values('Instrumentalist');
INSERT INTO tlc_tt_strings (str) values('It takes a number of folks to make Sunday Worship happen.  How do you want to help?');
INSERT INTO tlc_tt_strings (str) values('Jira');
INSERT INTO tlc_tt_strings (str) values('Mail Chimp');
INSERT INTO tlc_tt_strings (str) values('Marcello will continue to use both, but do you have a personal preference?');
INSERT INTO tlc_tt_strings (str) values('Multi Select #1');
INSERT INTO tlc_tt_strings (str) values('Multi Select #2');
INSERT INTO tlc_tt_strings (str) values('Other Ways you can help with communication');
INSERT INTO tlc_tt_strings (str) values('Other');
INSERT INTO tlc_tt_strings (str) values('Outreach');
INSERT INTO tlc_tt_strings (str) values('Participate');
INSERT INTO tlc_tt_strings (str) values('Pick whichever answer best applies.  Or provide your own if you don''t like the options provided');
INSERT INTO tlc_tt_strings (str) values('Pick whichever answer best applies.');
INSERT INTO tlc_tt_strings (str) values('Pick whichever answer or answers best apply.  Provide your own if you think we missed something.');
INSERT INTO tlc_tt_strings (str) values('Pick whichever answer or answers best apply.');
INSERT INTO tlc_tt_strings (str) values('Plan');
INSERT INTO tlc_tt_strings (str) values('Review');
INSERT INTO tlc_tt_strings (str) values('Ring bells');
INSERT INTO tlc_tt_strings (str) values('Routinely look through the website looking for out of date or missing content');
INSERT INTO tlc_tt_strings (str) values('SME');
INSERT INTO tlc_tt_strings (str) values('Same roles as for Facebook');
INSERT INTO tlc_tt_strings (str) values('Scrum Master');
INSERT INTO tlc_tt_strings (str) values('Section 1');
INSERT INTO tlc_tt_strings (str) values('Section 3');
INSERT INTO tlc_tt_strings (str) values('Section 4');
INSERT INTO tlc_tt_strings (str) values('Section 5');
INSERT INTO tlc_tt_strings (str) values('Section 6');
INSERT INTO tlc_tt_strings (str) values('Section 7');
INSERT INTO tlc_tt_strings (str) values('Section Deux');
INSERT INTO tlc_tt_strings (str) values('Select Question #1');
INSERT INTO tlc_tt_strings (str) values('Select Question #2');
INSERT INTO tlc_tt_strings (str) values('Service');
INSERT INTO tlc_tt_strings (str) values('Sing');
INSERT INTO tlc_tt_strings (str) values('So, what iss up?');
INSERT INTO tlc_tt_strings (str) values('Soprano');
INSERT INTO tlc_tt_strings (str) values('Subscribe');
INSERT INTO tlc_tt_strings (str) values('Tech Advisor');
INSERT INTO tlc_tt_strings (str) values('Tenor');
INSERT INTO tlc_tt_strings (str) values('This is popup info.  Just here to see if popups are working');
INSERT INTO tlc_tt_strings (str) values('This is where the text goes.  Skipping markdown/HTML for now (**mostly**).  But am adding a some italics and *bold*.');
INSERT INTO tlc_tt_strings (str) values('To be a community is to work and play together.  What ideas do you have?');
INSERT INTO tlc_tt_strings (str) values('Transportation');
INSERT INTO tlc_tt_strings (str) values('Trinity also keeps a presence on Instagram.');
INSERT INTO tlc_tt_strings (str) values('Trinity keeps a presence on Facebook');
INSERT INTO tlc_tt_strings (str) values('Trinity keeps a presence on Facebook<br><ul><li>Admins have ability to log into our busieness account</li><li>Editors can post as Trinity.</li><li>Subscribers receive posts in their feed and can post as themselves on our page.</li></ul>');
INSERT INTO tlc_tt_strings (str) values('We are God''s hands.  How can you help your neighbor?');
INSERT INTO tlc_tt_strings (str) values('We need help with our social media presence...');
INSERT INTO tlc_tt_strings (str) values('Welcome');
INSERT INTO tlc_tt_strings (str) values('What do you play');
INSERT INTO tlc_tt_strings (str) values('What else would you like us to know?');
INSERT INTO tlc_tt_strings (str) values('What would you like us to know?');
INSERT INTO tlc_tt_strings (str) values('Why or why not?');
INSERT INTO tlc_tt_strings (str) values('Will attend rehearsals');
INSERT INTO tlc_tt_strings (str) values('Will attend worship');
INSERT INTO tlc_tt_strings (str) values('Worship');
INSERT INTO tlc_tt_strings (str) values('Would you like to see more or less bells?');
INSERT INTO tlc_tt_strings (str) values('Would you like to see more or less instrumental music?');
INSERT INTO tlc_tt_strings (str) values('Yes/No Question');
INSERT INTO tlc_tt_strings (str) values('Your thoughts?');
INSERT INTO tlc_tt_strings (str) values('community');
INSERT INTO tlc_tt_strings (str) values('some words about section 1');
INSERT INTO tlc_tt_strings (str) values('some words about section 3');
INSERT INTO tlc_tt_strings (str) values('some words about section 4');
INSERT INTO tlc_tt_strings (str) values('some words about section 5');
INSERT INTO tlc_tt_strings (str) values('some words about section 6');
INSERT INTO tlc_tt_strings (str) values('some words about section 7');
INSERT INTO tlc_tt_strings (str) values('Website');
INSERT INTO tlc_tt_strings (str) values('A/V Team');
INSERT INTO tlc_tt_strings (str) values('Info Text (archived)');
INSERT INTO tlc_tt_strings (str) values('Yes/No Question (archived)');
INSERT INTO tlc_tt_strings (str) values('2024 Time and Talent Survey');
INSERT INTO tlc_tt_strings (str) values('2025 Time and Talent Survey');
INSERT INTO tlc_tt_strings (str) values('Website Folks');
INSERT INTO tlc_tt_strings (str) values('Musicians');

INSERT INTO tlc_tt_settings (name, value) 
     VALUES ('admin-lock','YMO85U33OW|1750036631|Site Admin'),
            ('app_logo','TrinityLutheran_Logo.png'),
            ('is_dev','1'),
            ('log_file','tlc-ttsurvey.log'),
            ('log_level','3'),
            ('primary_admin','mikemayer67'),
            ('smtp_auth','1'),
            ('smtp_debug','2'),
            ('smtp_host','smtp.gmail.com'),
            ('smtp_password','hbav deuj uogk njzh'),
            ('smtp_username','trinityelcawebapps@gmail.com');

INSERT INTO tlc_tt_userids (userid, fullname, email, token, password, anonid, admin) VALUES ('ewemeeweme','mrs. mayer','valerie@vmwishes.com','QGOS7PI8VEB1V3P6389Q284Z2','$2y$10$nhSQN18GJVF4Fqwh7LCQ3ePpj3WAG2rUmsUQehjDGZzIdejGrLNeK','$2y$10$FrQAyh7ygCADJenOpxene./UXYwnTm8DA73RumWJKqXg.TP4VtePK',0),('kitkat15','KitKat Lardo Mayer','NULL','1234567890','$2y$12$lFbHBX49Tb9Tr8bPXdr3PO92EqgqKb3VN/AwaIlYS4oo6mP8JNUOG','$2y$12$dHeRZDElHi3KqxnMs8CYTeVZLqZDeYGVU0o2Qqr8DCHieFtewUb9m',0),('memeeweewe','mr mayer','NULL','ZM65V4CIHKE24M2OVP11XNQGF','$2y$10$BddgS0luGppaDgcG9NuVI.D4fcCR6P/P5FkTh0nbGnYXyg12g9hOC','$2y$10$QsHijnfECcE1/hT7fHVgwOCFSOI.9G5JuPRJ9ESVkmjBvk0boWFEO',0),('mikemayer67','Mike Mayer','mikemayer67@vmwishes.com','1234567890','$2y$10$07PK3o0J.AhcGNVNSNQRZeEFFeTFHt6IHAobjTVvhnHX9Pr3lJ3dq','$2y$12$CZO1nM3fj4bsRKDigJQzo.IPhCpUjqMpvJ0n5nKUKjPursF5j4EAO',0),('newtest123','Justa Test Subject','mikemayer67@vmwishes.com','7T6KRG9XFDJ3AI6ZM4AHEHQT1','$2y$10$ej6q..eR/BBS3ZYbo1KP0Ox8e4IkPQGchzc9RkNUO4eJIu.mZOQpW','$2y$10$0QAa.R9sXb88iSO2nYfPneENLzU6dbHtNejIGovktuhMaHXEunGDO',0),('newtest124','Justa Test Subject','NULL','7JSLV1DED57HGCC6QU78LQU74','$2y$10$QyMMKHp2nb5E7mGbz6APvuYBjLsrXCmviRQJzgd1Tzscluk9aU72S','$2y$10$OsN1VFyYZczmy8k7UjyeGOy1qt42VNWep67kK7/V5bOLYLqPasGT.',0),('shadowcat','Ricky LeChat Mayer','mikemayer67@vmwishes.com','4SS2V72GT33X25P4U1W2E9Q7Q','$2y$10$Iihq0p6R0jySdWqhhzBzoOFVvzCvyIpeNvu2VqpNoKaePxjp97NPa','$2y$10$LzppRQEltRDppcLBdl/H5.b85T5tnTK5faYVN6g7ZpjXcQkQ5fHTu',0),('snickers','Iama Krazy Kat','NULL','1234567890','$2y$12$0QvuwzOA0Djtv8JjVNrGEuyBjYqNcyCg.W3UyWedb/XBX4eNOeBL2','$2y$12$290O0qvJp9aBBnm17Xvi7up/.r4CgUAGIjFxsPCk8NICCWYhRHEBS',0);

INSERT INTO tlc_tt_roles (userid, admin, content, tech) VALUES ('ewemeeweme',0,0,1),('kitkat15',0,1,1),('memeeweewe',0,0,1),('mikemayer67',0,1,1),('newtest123',0,1,0),('newtest124',0,1,0),('shadowcat',1,0,0),('snickers',0,0,0);

INSERT INTO tlc_tt_survey_status (survey_id,parent_id,created,active,closed)
     VALUES (1, NULL, '2024-03-15 11:44:27', '2024-04-01 00:00:00', '2024-06-30 00:00:00'), 
            (2, 1,    '2025-03-15 11:44:27',  NULL,                  NULL), 
            (3, NULL, '2024-08-10 19:12:20', '2024-08-15 00:00:00', '2024-10-01 00:00:00'), 
            (4, NULL, '2024-09-10 19:12:20', '2024-09-15 00:00:00', '2024-11-01 00:00:00');

INSERT INTO tlc_tt_survey_revisions 
            (survey_id,survey_rev,title_sid) 
     VALUES (1,1,100),
            (2,1,101),
            (2,2,101),
            (3,1,102),
            (3,2,102),
            (4,1,103);

INSERT INTO tlc_tt_survey_options (survey_id, survey_rev, option_id, text_sid) 
      VALUES (1,1,1,40), (1,1,2,16), (1,1,3,45), (1,1,4,19),
             (2,1,1,40), (2,1,2,16), (2,1,3,45), (2,1,4,71),
             (3,1,1,40), (3,1,2,16), (3,1,3,32), (3,1,4,66), (3,1,5,2),  (3,1,6,20), (3,1,7,65),
             (4,1,1,40), (4,1,2,16), (4,1,3,45), (4,1,4,19), (4,1,8,9),  (4,1,9,67), (4,1,10,4), (4,1,11,64);

INSERT INTO tlc_tt_survey_options
SELECT survey_id, 2, option_id, text_sid from tlc_tt_survey_options where survey_id in (2,3);

UPDATE tlc_tt_survey_options set text_sid=51 where survey_id=3 and survey_rev=2 and option_id=2;
UPDATE tlc_tt_survey_options set text_sid=49 where survey_id=3 and survey_rev=2 and option_id=4;

INSERT INTO tlc_tt_survey_sections 
            (survey_id, survey_rev, sequence, name_sid, labeled, description_sid, feedback_sid)
     VALUES (1, 1, 1, 84, 1, 31,NULL),
            (1, 1, 2, 39, 1, 75,NULL),
            (1, 1, 3, 89, 1, 70,NULL),
            (2, 1, 1, 77, 1, NULL,NULL),
            (2, 1, 2, 52, 1, 90, NULL),
            (2, 1, 3, 58, 1, 12, 63 ),
            (2, 1, 4, 53, 1, 91, NULL),
            (2, 1, 5, 54, 1, 92, NULL),
            (2, 1, 7, 55, 1, 93, NULL),
            (2, 1, 9, 56, 1, 94, NULL),
            (2, 1, 8, 57, 1, 95, NULL),
            (3, 1, 1, 96, 1, NULL,NULL),
            (3, 1, 2, 14, 1, NULL,NULL),
            (3, 1, 3, 97, 1, NULL,NULL),
            (4, 1, 1, 13, 1, NULL,NULL),
            (4, 1, 2, 10, 1, NULL,NULL),
            (4, 1, 3, 30,1, NULL,NULL);

INSERT INTO tlc_tt_survey_sections 
SELECT survey_id,2,sequence,name_sid,labeled,description_sid,feedback_sid
FROM tlc_tt_survey_sections where survey_id in (2,3) and survey_rev=1;

update tlc_tt_survey_sections set feedback_sid=80    where survey_id=1 and survey_rev=2 and sequence=2;
update tlc_tt_survey_sections set name_sid=23        where survey_id=2 and survey_rev=2 and sequence=1;
update tlc_tt_survey_sections set feedback_sid=6     where survey_id=2 and survey_rev=2 and sequence=1;
update tlc_tt_survey_sections set description_sid=76 where survey_id=3 and survey_rev=2 and sequence=2;

INSERT INTO tlc_tt_survey_questions 
            (question_id, survey_id, survey_rev, wording_sid, question_type, multiple, other_sid, qualifier_sid, description_sid, info_sid) 
     VALUES (  1,1,1,27,'INFO',NULL,NULL,NULL,NULL,69),
            (  2,1,1,87,'BOOL',NULL,NULL,81,11,68),
            (  3,1,1,59,'OPTIONS',0,38,5, 41, 68),
            (  4,1,1,60,'OPTIONS',0,NULL,5, 42, 68),
            (  5,1,1,35,'OPTIONS',1,38,5, 43, 68),
            (  6,1,1,36,'OPTIONS',1,NULL,5,44,68),
            (  7,1,1,88,'FREETEXT',NULL,NULL,NULL,79,68),
            ( 68,1,1,27,'INFO',NULL,NULL,NULL,NULL,69),
            ( 69,1,1,87,'BOOL',NULL,NULL,81,11,68),
            (114,3,1,8,'OPTIONS',NULL,38,21,NULL,NULL),
            (115,3,1,15,'OPTIONS',NULL,38,NULL,NULL,NULL),
            (116,3,1,46,'BOOL',NULL,NULL,NULL,48,NULL),
            (117,3,1,22,'OPTIONS',NULL,NULL,NULL,73,3),
            (117,3,2,22,'OPTIONS',NULL,NULL,NULL,74,NULL),
            (118,3,2,28,'OPTIONS',NULL,NULL,NULL,72,50),
            (119,3,1,33,'OPTIONS',NULL,NULL,NULL,NULL,NULL),
            (119,3,2,33,'OPTIONS',NULL,NULL,NULL,NULL,NULL),
            (120,3,1,37,'FREETEXT',NULL,NULL,NULL,NULL,NULL),
            (120,3,2,37,'FREETEXT',NULL,NULL,NULL,NULL,NULL),
            (121,4,1,82,'BOOL',NULL,NULL,NULL,NULL,NULL),
            (122,4,1,83,'BOOL',NULL,NULL,NULL,NULL,NULL),
            (123,4,1,17,'BOOL',NULL,NULL,NULL,NULL,NULL),
            (124,4,1,25,'BOOL',NULL,NULL,NULL,NULL,24),
            (125,4,1,47,'OPTIONS',NULL,NULL,NULL,NULL,NULL),
            (126,4,1,17,'BOOL',NULL,NULL,NULL,NULL,NULL),
            (127,4,1,1,'BOOL',NULL,NULL,29,NULL,NULL),
            (128,4,1,62,'OPTIONS',NULL,NULL,NULL,NULL,NULL),
            (129,4,1,78,'FREETEXT',NULL,NULL,NULL,NULL,NULL),
            (130,4,1,26,'OPTIONS',NULL,38,7,NULL,NULL),
            (131,4,1,86,'FREETEXT',NULL,NULL,NULL,NULL,NULL),
            (132,4,1,85,'FREETEXT',NULL,NULL,NULL,NULL,NULL),
            (133,4,1,18,'FREETEXT',NULL,NULL,NULL,34,NULL);

INSERT INTO tlc_tt_survey_questions
     SELECT question_id, 2, survey_rev, wording_sid, question_type, multiple, other_sid, qualifier_sid, description_sid, info_sid 
       from tlc_tt_survey_questions where survey_id=1;

INSERT INTO tlc_tt_survey_questions
     SELECT  7+question_id, survey_id, survey_rev, wording_sid, question_type, multiple, other_sid, qualifier_sid, description_sid, info_sid
       from tlc_tt_survey_questions where survey_id in (1,2) and question_id < 8;

INSERT INTO tlc_tt_survey_questions
     SELECT 14+question_id, survey_id, survey_rev, wording_sid, question_type, multiple, other_sid, qualifier_sid, description_sid, info_sid 
       from tlc_tt_survey_questions where survey_id in (1,2) and question_id < 8;

INSERT INTO tlc_tt_survey_questions
     SELECT 21+question_id, survey_id, survey_rev, wording_sid, question_type, multiple, other_sid, qualifier_sid, description_sid, info_sid 
       from tlc_tt_survey_questions where survey_id=2 and question_id < 8;

INSERT INTO tlc_tt_survey_questions
     SELECT 28+question_id, survey_id, survey_rev, wording_sid, question_type, multiple, other_sid, qualifier_sid, description_sid, info_sid 
       from tlc_tt_survey_questions where survey_id=2 and question_id < 8;

INSERT INTO tlc_tt_survey_questions
     SELECT 35+question_id, survey_id, survey_rev, wording_sid, question_type, multiple, other_sid, qualifier_sid, description_sid, info_sid 
       from tlc_tt_survey_questions where survey_id=2 and question_id < 8;

INSERT INTO tlc_tt_survey_questions
     SELECT 42+question_id, survey_id, survey_rev, wording_sid, question_type, multiple, other_sid, qualifier_sid, description_sid, info_sid 
       from tlc_tt_survey_questions where survey_id=2 and question_id < 8;

INSERT INTO tlc_tt_survey_questions
     SELECT 49+question_id, survey_id, survey_rev, wording_sid, question_type, multiple, other_sid, qualifier_sid, description_sid, info_sid 
       from tlc_tt_survey_questions where survey_id=2 and question_id < 8;

INSERT INTO tlc_tt_survey_questions
     SELECT question_id, survey_id, 2, wording_sid, question_type, multiple, other_sid, qualifier_sid, description_sid, info_sid
       from tlc_tt_survey_questions where survey_id=2 and survey_rev=1;

UPDATE tlc_tt_survey_questions set wording_sid=98 where survey_id=2 and survey_rev=2 and question_id=68;
UPDATE tlc_tt_survey_questions set wording_sid=99 where survey_id=2 and survey_rev=2 and question_id=69;

INSERT INTO tlc_tt_question_map 
            (survey_id, survey_rev,section_seq,question_seq,question_id)
     VALUES (1,1,1,1,  1),
            (1,1,1,2,  2),
            (1,1,1,3,  3),
            (1,1,1,4,  4),
            (1,1,1,5,  5),
            (1,1,1,6,  6),
            (1,1,1,7,  7),
            (1,1,1,8, 68),
            (1,1,1,9, 69),
            (3,1,1,1,114),
            (3,1,1,2,115),
            (3,1,1,3,116),
            (3,1,2,1,117),
            (3,2,2,1,117),
            (3,2,2,2,118),
            (3,1,2,2,119),
            (3,2,2,3,119),
            (3,1,2,3,120),
            (3,2,2,4,120),
            (4,1,2,1,121),
            (4,1,2,2,122),
            (4,1,2,3,123),
            (4,1,2,4,124),
            (4,1,2,5,125),
            (4,1,1,2,126),
            (4,1,1,3,127),
            (4,1,1,1,128),
            (4,1,3,1,129),
            (4,1,3,2,130),
            (4,1,3,3,131),
            (4,1,3,4,132),
            (4,1,3,5,133);

INSERT INTO tlc_tt_question_map
     SELECT 2, survey_rev, section_seq, question_seq, question_id
       from tlc_tt_question_map where survey_id=1;

INSERT INTO tlc_tt_question_map
     SELECT survey_id, survey_rev, 1+section_seq, 1+(7+question_seq)%10, 7+question_id
       from tlc_tt_question_map where survey_id in (1,2) and section_seq=1 and question_id < 8;

INSERT INTO tlc_tt_question_map
     SELECT survey_id, survey_rev, 1+section_seq, 1+(7+question_seq)%10, 7+question_id
       from tlc_tt_question_map where survey_id in (1,2) and section_seq=2;

INSERT INTO tlc_tt_question_map
     SELECT survey_id, survey_rev, 1+section_seq, 1+(7+question_seq)%10, 7+question_id
       from tlc_tt_question_map where survey_id=2 and section_seq=3;

INSERT INTO tlc_tt_question_map
     SELECT survey_id, survey_rev, 1+section_seq, 1+(7+question_seq)%10, 7+question_id
       from tlc_tt_question_map where survey_id=2 and section_seq=4;

INSERT INTO tlc_tt_question_map
     SELECT survey_id, survey_rev, 2+section_seq, 1+(7+question_seq)%10, 7+question_id
       from tlc_tt_question_map where survey_id=2 and section_seq=5;

INSERT INTO tlc_tt_question_map
     SELECT survey_id, survey_rev, 1+section_seq, 1+(7+question_seq)%10, 7+question_id
       from tlc_tt_question_map where survey_id=2 and section_seq=7;

INSERT INTO tlc_tt_question_map
     SELECT survey_id, survey_rev, 1+section_seq, 1+(7+question_seq)%10, 7+question_id
       from tlc_tt_question_map where survey_id=2 and section_seq=8;

INSERT INTO tlc_tt_question_map
     SELECT 2,2,section_seq, question_seq, question_id
       from tlc_tt_question_map where survey_id=2 and survey_rev=1;

INSERT INTO tlc_tt_question_options VALUES (1,1,  3,0,1,3);
INSERT INTO tlc_tt_question_options VALUES (1,1,  3,0,2,2);
INSERT INTO tlc_tt_question_options VALUES (1,1,  3,0,3,1);
INSERT INTO tlc_tt_question_options VALUES (1,1,  4,0,1,3);
INSERT INTO tlc_tt_question_options VALUES (1,1,  4,0,2,2);
INSERT INTO tlc_tt_question_options VALUES (1,1,  4,1,1,1);
INSERT INTO tlc_tt_question_options VALUES (1,1,  5,0,1,1);
INSERT INTO tlc_tt_question_options VALUES (1,1,  5,0,2,2);
INSERT INTO tlc_tt_question_options VALUES (1,1,  5,1,1,3);
INSERT INTO tlc_tt_question_options VALUES (1,1,  5,1,2,4);
INSERT INTO tlc_tt_question_options VALUES (1,1,  6,0,1,1);
INSERT INTO tlc_tt_question_options VALUES (1,1,  6,0,2,2);
INSERT INTO tlc_tt_question_options VALUES (1,1,  6,1,1,3);
INSERT INTO tlc_tt_question_options VALUES (1,1,  6,1,2,4);
INSERT INTO tlc_tt_question_options VALUES (3,1,114,0,1,1);
INSERT INTO tlc_tt_question_options VALUES (3,1,114,0,2,2);
INSERT INTO tlc_tt_question_options VALUES (3,1,114,1,1,3);
INSERT INTO tlc_tt_question_options VALUES (3,1,114,1,2,4);
INSERT INTO tlc_tt_question_options VALUES (3,1,115,0,1,1);
INSERT INTO tlc_tt_question_options VALUES (3,1,115,0,2,2);
INSERT INTO tlc_tt_question_options VALUES (3,1,115,1,1,3);
INSERT INTO tlc_tt_question_options VALUES (3,1,117,0,1,5);
INSERT INTO tlc_tt_question_options VALUES (3,1,117,0,2,6);
INSERT INTO tlc_tt_question_options VALUES (3,1,117,1,1,7);
INSERT INTO tlc_tt_question_options VALUES (3,2,117,0,1,5);
INSERT INTO tlc_tt_question_options VALUES (3,2,117,0,2,6);
INSERT INTO tlc_tt_question_options VALUES (3,2,117,1,1,7);
INSERT INTO tlc_tt_question_options VALUES (3,2,118,0,1,5);
INSERT INTO tlc_tt_question_options VALUES (3,2,118,0,2,6);
INSERT INTO tlc_tt_question_options VALUES (3,2,118,1,1,7);
INSERT INTO tlc_tt_question_options VALUES (3,1,119,0,1,5);
INSERT INTO tlc_tt_question_options VALUES (3,1,119,0,2,6);
INSERT INTO tlc_tt_question_options VALUES (3,2,119,0,1,5);
INSERT INTO tlc_tt_question_options VALUES (3,2,119,0,2,6);
INSERT INTO tlc_tt_question_options VALUES (4,1,125,0,1,8);
INSERT INTO tlc_tt_question_options VALUES (4,1,125,0,2,9);
INSERT INTO tlc_tt_question_options VALUES (4,1,125,0,3,10);
INSERT INTO tlc_tt_question_options VALUES (4,1,125,0,4,11);
INSERT INTO tlc_tt_question_options VALUES (4,1,128,0,1,8);
INSERT INTO tlc_tt_question_options VALUES (4,1,128,0,2,9);
INSERT INTO tlc_tt_question_options VALUES (4,1,128,0,3,10);
INSERT INTO tlc_tt_question_options VALUES (4,1,128,0,4,11);
INSERT INTO tlc_tt_question_options VALUES (4,1,130,0,1,2);
INSERT INTO tlc_tt_question_options VALUES (4,1,130,0,2,3);
INSERT INTO tlc_tt_question_options VALUES (4,1,130,0,3, 4);

INSERT INTO tlc_tt_question_options SELECT 2,1,question_id,secondary,sequence,option_id FROM tlc_tt_question_options where survey_id=1;

INSERT INTO tlc_tt_question_options SELECT survey_id,survey_rev, 7+question_id,secondary,sequence,option_id FROM tlc_tt_question_options where survey_id in (1,2) and question_id < 7;
INSERT INTO tlc_tt_question_options SELECT survey_id,survey_rev,14+question_id,secondary,sequence,option_id FROM tlc_tt_question_options where survey_id in (1,2) and question_id < 7;
INSERT INTO tlc_tt_question_options SELECT survey_id,survey_rev,21+question_id,secondary,sequence,option_id FROM tlc_tt_question_options where survey_id=2 and question_id < 7;
INSERT INTO tlc_tt_question_options SELECT survey_id,survey_rev,28+question_id,secondary,sequence,option_id FROM tlc_tt_question_options where survey_id=2 and question_id < 7;
INSERT INTO tlc_tt_question_options SELECT survey_id,survey_rev,35+question_id,secondary,sequence,option_id FROM tlc_tt_question_options where survey_id=2 and question_id < 7;
INSERT INTO tlc_tt_question_options SELECT survey_id,survey_rev,42+question_id,secondary,sequence,option_id FROM tlc_tt_question_options where survey_id=2 and question_id < 7;
INSERT INTO tlc_tt_question_options SELECT survey_id,survey_rev,49+question_id,secondary,sequence,option_id FROM tlc_tt_question_options where survey_id=2 and question_id < 7;

INSERT INTO tlc_tt_question_options 
       SELECT 2,2,question_id,0,sequence,option_id FROM tlc_tt_question_options where survey_id=2 and secondary=0 and question_id in 
       ( SELECT distinct question_id from tlc_tt_question_options where survey_id=2 and survey_rev=1);

INSERT INTO tlc_tt_question_options 
       SELECT 2,2,question_id,0,10+sequence,option_id FROM tlc_tt_question_options where survey_id=2 and secondary=1 and question_id in 
       ( SELECT distinct question_id from tlc_tt_question_options where survey_id=2 and survey_rev=1);

-- cannot do this sooner or question options insertion will trip FK constraint
-- easier to delete here with cascade than to modify the insert logic
DELETE FROM tlc_tt_question_map 
      WHERE survey_id=2 AND survey_rev=2 and question_id in (52,53);

DELETE FROM tlc_tt_survey_questions
      WHERE survey_id=2 AND question_id=5;
