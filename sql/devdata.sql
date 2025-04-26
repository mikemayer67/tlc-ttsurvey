
delete from tlc_tt_element_options;
delete from tlc_tt_survey_elements;
delete from tlc_tt_survey_sections;
delete from tlc_tt_survey_options;
delete from tlc_tt_surveys;

delete from tlc_tt_reset_tokens;
delete from tlc_tt_roles;
delete from tlc_tt_anonids;
delete from tlc_tt_userids;

delete from tlc_tt_settings;

alter table tlc_tt_surveys AUTO_INCREMENT=1;
alter table tlc_tt_survey_options AUTO_INCREMENT=1;
alter table tlc_tt_survey_elements AUTO_INCREMENT=1;


insert into tlc_tt_settings (name,value) values
  ('app_logo',      'TrinityLutheran_Logo.png'     ),
  ('is_dev',        '1'                            ),
  ('log_file',      'tlc-ttsurvey.log'             ),
  ('log_level',     '3'                            ),
  ('primary_admin', 'mikemayer67'                  ),
  ('smtp_auth',     '1'                            ),
  ('smtp_debug',    '2'                            ),
  ('smtp_host',     'smtp.gmail.com'               ),
  ('smtp_password', 'hbav deuj uogk njzh'          ),
  ('smtp_username', 'trinityelcawebapps@gmail.com' );

insert into tlc_tt_userids (userid,fullname,email,token,password,anonid) values
  ('ewemeeweme',  'mrs.mayer',        'valerie@vmwishes.com',     'QGOS7PI8VEB1V3P6389Q284Z2', '$2y$10$nhSQN18GJVF4Fqwh7LCQ3ePpj3WAG2rUmsUQehjDGZzIdejGrLNeK', '$2y$10$FrQAyh7ygCADJenOpxene./UXYwnTm8DA73RumWJKqXg.TP4VtePK'),                
  ('kitkat15',    'KitKatLardoMayer', 'NULL',                     '1234567890',                '$2y$12$lFbHBX49Tb9Tr8bPXdr3PO92EqgqKb3VN/AwaIlYS4oo6mP8JNUOG', '$2y$12$dHeRZDElHi3KqxnMs8CYTeVZLqZDeYGVU0o2Qqr8DCHieFtewUb9m'),                
  ('memeeweewe',  'mrmayer',          'NULL',                     'ZM65V4CIHKE24M2OVP11XNQGF', '$2y$10$BddgS0luGppaDgcG9NuVI.D4fcCR6P/P5FkTh0nbGnYXyg12g9hOC', '$2y$10$QsHijnfECcE1/hT7fHVgwOCFSOI.9G5JuPRJ9ESVkmjBvk0boWFEO'),                
  ('mikemayer67', 'MikeMayer',        'mikemayer67@vmwishes.com', '1234567890',                '$2y$10$07PK3o0J.AhcGNVNSNQRZeEFFeTFHt6IHAobjTVvhnHX9Pr3lJ3dq', '$2y$12$CZO1nM3fj4bsRKDigJQzo.IPhCpUjqMpvJ0n5nKUKjPursF5j4EAO'),                
  ('newtest123',  'JustaTestSubject', 'mikemayer67@vmwishes.com', '7T6KRG9XFDJ3AI6ZM4AHEHQT1', '$2y$10$ej6q..eR/BBS3ZYbo1KP0Ox8e4IkPQGchzc9RkNUO4eJIu.mZOQpW', '$2y$10$0QAa.R9sXb88iSO2nYfPneENLzU6dbHtNejIGovktuhMaHXEunGDO'),                
  ('newtest124',  'JustaTestSubject', 'NULL',                     '7JSLV1DED57HGCC6QU78LQU74', '$2y$10$QyMMKHp2nb5E7mGbz6APvuYBjLsrXCmviRQJzgd1Tzscluk9aU72S', '$2y$10$OsN1VFyYZczmy8k7UjyeGOy1qt42VNWep67kK7/V5bOLYLqPasGT.'),                
  ('shadowcat',   'RickyLeChatMayer', 'mikemayer67@vmwishes.com', '4SS2V72GT33X25P4U1W2E9Q7Q', '$2y$10$Iihq0p6R0jySdWqhhzBzoOFVvzCvyIpeNvu2VqpNoKaePxjp97NPa', '$2y$10$LzppRQEltRDppcLBdl/H5.b85T5tnTK5faYVN6g7ZpjXcQkQ5fHTu'),                
  ('snickers',    'IamaKrazyKat',     'NULL',                     '1234567890',                '$2y$12$0QvuwzOA0Djtv8JjVNrGEuyBjYqNcyCg.W3UyWedb/XBX4eNOeBL2', '$2y$12$290O0qvJp9aBBnm17Xvi7up/.r4CgUAGIjFxsPCk8NICCWYhRHEBS');

insert into tlc_tt_roles (userid,admin,content,tech) values
  ('ewemeeweme',  0, 0, 1),
  ('kitkat15',    0, 1, 1),
  ('memeeweewe',  0, 0, 1),
  ('mikemayer67', 0, 1, 1),
  ('newtest123',  0, 1, 0),
  ('newtest124',  0, 1, 0),
  ('shadowcat',   1, 0, 0),
  ('snickers',    0, 0, 0);

insert into tlc_tt_surveys (title,created,active,closed,parent,revision) values
  ('2024 Time and Talent Survey'        , '2025-03-15 11:44:27',' 2024-04-01 00:00:00',' 2024-06-30 00:00:00', NULL, 2 ),
  ('2025 Trinity Time and Talent Survey', '2025-03-15 11:44:27',  NULL                ,  NULL                ,    1, 1 ),
  ('Website Folks'                      , '2025-04-10 19:12:20',' 2025-04-10 00:00:00',' 2024-10-01 00:00:00', NULL, 2 ),
  ('Musicians'                          , '2025-04-10 19:12:20',' 2025-04-15 00:00:00',' 2024-11-01 00:00:00', NULL, 1 );

update tlc_tt_surveys set parent=1 where id=2;

insert into tlc_tt_survey_options (id,survey_id,survey_rev,text) values
  (1, 1, 1, 'Participate'), 
  (1, 2, 1, 'Participate'), 
  (1, 3, 1, 'Participate'), 
  (1, 4, 1, 'Participate'), 
  (2, 1, 1, 'Coordinate'), 
  (2, 2, 1, 'Coordinate'), 
  (2, 3, 1, 'Coordinate'), 
  (2, 3, 2, 'Scrum Master'),
  (2, 4, 1, 'Coordinate'), 
  (3, 1, 1, 'Plan'), 
  (3, 2, 1, 'Plan'), 
  (3, 3, 1, 'Jira'), 
  (3, 4, 1, 'Plan'),
  (4, 1, 1, 'Drive'),
  (4, 2, 1, 'Transportation'),
  (4, 3, 1, 'Tech Advisor'),
  (4, 3, 2, 'SME'),
  (4, 4, 1, 'Drive'),
  (5, 3, 1, 'Admin'),
  (6, 3, 1, 'Editor'),
  (7, 3, 1, 'Subscribe'),
  (8, 4, 1, 'Bass'),
  (9, 4, 1, 'Tenor'),
  (10, 4, 1, 'Alto'),
  (11, 4, 1, 'Soprano')
  ;

insert into tlc_tt_survey_sections (survey_id,survey_rev,sequence,name,description,feedback) values
  (1, 1, 1, 'Worship',         'It takes a number of folks to make Sunday Worship happen.  How do you want to help?', 0),
  (1, 1, 2, 'Outreach',        'We are God''s hands.  How can you help your neighbor?', 0),
  (1, 2, 2, 'Outreach',        'We are God''s hands.  How can you help your neighbor?', 1),
  (1, 3, 2, 'Service',         'We are God''s hands.  How can you help your neighbor?', 0),
  (1, 1, 3, 'community',       'To be a community is to work and play together.  What ideas do you have?', 0),
  (2, 1, 1, 'Worship',         'It takes a number of folks to make Sunday Worship happen.  How do you want to help?', 1),
  (2, 1, 2, 'Community',       'To be a community is to work and play together.  What ideas do you have?', 1),
  (2, 1, 3, 'Service',         'We are God''s hands.  How can you help your neighbor?', 0),
  (3, 1, 1, 'Website',         NULL, 0),
  (3, 1, 2, 'Communications',  NULL, 0),
  (3, 2, 2, 'Communications',  'We need help with our social media presence...', 0),
  (3, 1, 3, 'A/V Team',        NULL, 0),
  (4, 1, 1, 'Choir',           NULL, 0),
  (4, 1, 2, 'Bells',           NULL, 0),
  (4, 1, 3, 'Instrumentalist', NULL, 0);

insert into tlc_tt_survey_elements (id,survey_id,survey_rev,section_seq,sequence,label,element_type,other,qualifier,description,info) values
  -- Survey #1 --
  ( 1, 1, 1, 1, 1, 'Lector',          'BOOL',    NULL, NULL, NULL, 'Reads the lessons from the pulpit before the Gospel'),
  ( 2, 1, 1, 1, 2, 'Acolyte',         'BOOL',    NULL, NULL, NULL, 'Lights the candels'),
  ( 3, 1, 1, 1, 3, 'Usher',           'BOOL',    NULL, NULL, NULL, 'Assists with helping congregants...'),
  ( 4, 1, 1, 1, 4, 'Greeter',         'BOOL',    NULL, NULL, 'This is one of the most underappreciated and *most vital* roles...', 'Welcomes members/guets as they arrive'),
  ( 5, 1, 1, 2, 1, 'Little Pantry',   'OPTIONS', NULL, NULL, NULL, NULL),
  ( 6, 1, 1, 2, 2, 'Blood Drive',     'OPTIONS', NULL, NULL, NULL, NULL),
  ( 7, 1, 1, 2, 3, 'Food Drives',     'OPTIONS', NULL, NULL, NULL, NULL),
  ( 8, 1, 1, 2, 4, 'Guest Speakers',  'OPTIONS', NULL, 'Anyone in particular', NULL, NULL),
  (13, 1, 1, 2, 5, 'Handyman',        'OPTIONS', NULL, 'Any other skills', NULL, NULL),
  ( 9, 1, 1, 3, 1, 'Panckake Supper', 'OPTIONS', NULL, NULL, NULL, NULL),
  (10, 1, 1, 3, 2, 'Easter Branch',   'OPTIONS', NULL, NULL, NULL, NULL),
  (11, 1, 1, 3, 3, 'Cong. Meetings',  'OPTIONS', NULL, 'Which ones', NULL, NULL),
  (12, 1, 1, 3, 4, 'Game Night',      'OPTIONS', NULL, 'Which ones', NULL, NULL),
  -- Survey #2 --
  ( 1, 2, 1, 1, 1, 'Lector',          'BOOL',    NULL, NULL, NULL, 'Reads the lessons from the pulpit before the Gospel'),
  ( 2, 2, 1, 1, 2, 'Acolyte',         'BOOL',    NULL, NULL, NULL, 'Lights the candels'),
  ( 3, 2, 1, 1, 3, 'Usher',           'BOOL',    NULL, NULL, NULL, 'Assists with helping congregants...'),
  ( 4, 2, 1, 1, 4, 'Greeter',         'BOOL',    NULL, NULL, NULL, 'Assists with helping congregants...'),
  ( 5, 2, 1, 3, 1, 'Little Pantry',   'OPTIONS', NULL, NULL, NULL, NULL),
  ( 6, 2, 1, 3, 2, 'Blood Drive',     'OPTIONS', NULL, NULL, NULL, NULL),
  ( 7, 2, 1, 3, 3, 'Food Drives',     'OPTIONS', NULL, NULL, NULL, NULL),
  ( 8, 2, 1, 3, 4, 'Guest Speakers',  'OPTIONS', NULL, 'Anyone in particular', NULL, NULL),
  (13, 2, 1, 3, 5, 'Handyman',        'OPTIONS', NULL, 'Any other skills', NULL, NULL),
  ( 9, 2, 1, 2, 1, 'Panckake Supper', 'OPTIONS', NULL, NULL, NULL, NULL),
  (10, 2, 1, 2, 5, 'Easter Branch',   'OPTIONS', NULL, NULL, NULL, NULL),
  (11, 2, 1, 2, 3, 'Cong. Meetings',  'OPTIONS', NULL, 'Which ones', NULL, NULL),
  (12, 2, 1, 2, 4, 'Game Night',      'OPTIONS', NULL, 'Which ones', NULL, NULL),
  -- Survey #3 --
  (14, 3, 1, 1, 1, 'Backend',         'OPTIONS', 'Other', 'Experience', NULL, NULL),
  (15, 3, 1, 1, 2, 'Content',         'OPTIONS', 'Other', NULL, NULL, NULL),
  (16, 3, 1, 1, 3, 'Review',          'BOOL',    NULL, NULL, 'Routinely look through the website looking for out of date or missing content', NULL),
  (17, 3, 1, 2, 1, 'Facebook',        'OPTIONS', NULL, NULL, 'Trinity keeps a presence on Facebook', 'Admins have ability to log into our busieness account, Editors can post as Trinity. Subscribers recevie posts in their feed and can post as themselves on our page.'),
  (17, 3, 2, 2, 1, 'Facebook',        'OPTIONS', NULL, NULL, 'Trinity keeps a presence on Facebook<br><ul><li>Admins have ability to log into our busieness account</li><li>Editors can post as Trinity.</li><li>Subscribers receive posts in their feed and can post as themselves on our page.</li></ul>',NULL),
  (18, 3, 2, 2, 2, 'Instagram',       'OPTIONS', NULL, NULL, 'Trinity also keeps a presence on Instagram.','Same roles as for Facebook'),
  (19, 3, 1, 2, 2, 'Mail Chimp',      'OPTIONS', NULL, NULL, NULL, NULL),
  (19, 3, 2, 2, 3, 'Mail Chimp',      'OPTIONS', NULL, NULL, NULL, NULL),
  (20, 3, 1, 2, 3, 'Other Ways you can help with communication', 'FREETEXT', NULL, NULL, NULL, NULL),
  (20, 3, 2, 2, 4, 'Other Ways you can help with communication', 'FREETEXT', NULL, NULL, NULL, NULL),
  -- Survey #4 --
  (26, 4, 1, 1, 2, 'Direct',          'BOOL', NULL, NULL, NULL, NULL),
  (27, 4, 1, 1, 3, 'Accompany',       'BOOL', NULL, 'Instrument(s)', NULL, NULL),
  (28, 4, 1, 1, 1, 'Sing',            'OPTIONS', NULL, NULL, NULL, NULL),
  (21, 4, 1, 2, 1, 'Will attend rehearsals', 'BOOL', NULL, NULL, NULL, NULL),
  (22, 4, 1, 2, 2, 'Will attend worship',    'BOOL', NULL, NULL, NULL, NULL),
  (23, 4, 1, 2, 3, 'Direct',           'BOOL', NULL, NULL, NULL, NULL),
  (24, 4, 1, 2, 4, 'Help with Sound',  'BOOL', NULL, NULL, NULL, 'Help set up/tear down worship mics'),
  (25, 4, 1, 2, 5, 'Ring bells',       'OPTIONS', NULL, NULL, NULL, NULL),
  (29, 4, 1, 3, 1, 'What do you play', 'FREETEXT', NULL, NULL, NULL, NULL),
  (30, 4, 1, 3, 2, 'How would you like to help music', 'OPTIONS', 'Other', 'Availability', NULL, NULL),
  (31, 4, 1, 3, 3, 'Would you like to see more or less instrumental music?', 'FREETEXT', NULL, NULL, NULL, NULL),
  (32, 4, 1, 3, 4, 'Would you like to see more or less bells?', 'FREETEXT', NULL, NULL, NULL, NULL),
  (33, 4, 1, 3, 5, 'Do you prefer piano or organ during worship?','FREETEXT', NULL, NULL, 'Marcello will continue to use both, but do you have a personal preference?',NULL);

insert into tlc_tt_element_options (survey_id, survey_rev, element_id, sequence, option_id, secondary) values
  -- Survey #1 --
  (1, 1, 5, 1, 2, 0), (1, 1, 5, 2, 1, 0), 
  (1, 1, 6, 1, 2, 0), (1, 1, 6, 2, 1, 0), 
  (1, 1, 7, 1, 2, 0), (1, 1, 7, 2, 1, 0), (1, 1, 7, 3, 3, 0), 
  (1, 1, 8, 1, 2, 0), (1, 1, 8, 2, 1, 0), (1, 1, 8, 3, 4, 0), 
  (1, 1, 9, 1, 2, 0), (1, 1, 9, 2, 1, 0), (1, 1, 9, 3, 3, 0),
  (1, 1,10, 1, 2, 0), (1, 1,10, 2, 1, 0), (1, 1,10, 3, 3, 0),
  (1, 1,11, 1, 2, 0), (1, 1,11, 2, 1, 0), (1, 1,11, 3, 3, 0),
  (1, 1,12, 1, 2, 0), (1, 1,12, 2, 1, 0), (1, 1,12, 3, 3, 0), (1, 1,12, 4, 4, 0),
  (1, 1,13, 1, 2, 0), (1, 1,13, 2, 1, 0), 
  -- Survey #2 --
  (2, 1, 5, 1, 2, 0), (2, 1, 5, 2, 1, 0), 
  (2, 1, 6, 1, 2, 0), (2, 1, 6, 2, 1, 0), 
  (2, 1, 7, 1, 2, 0), (2, 1, 7, 2, 1, 0), (2, 1, 7, 3, 3, 0), 
  (2, 1, 8, 1, 2, 0), (2, 1, 8, 2, 1, 0), (2, 1, 8, 3, 4, 0), 
  (2, 1, 9, 1, 2, 0), (2, 1, 9, 2, 1, 0), (2, 1, 9, 3, 3, 0),
  (2, 1,10, 1, 2, 0), (2, 1,10, 2, 1, 0), (2, 1,10, 3, 3, 0),
  (2, 1,11, 1, 2, 0), (2, 1,11, 2, 1, 0), (2, 1,11, 3, 3, 0), (2, 1,11, 4, 4, 0),
  (2, 1,12, 1, 2, 0), (2, 1,12, 2, 1, 0), (2, 1,12, 3, 3, 0), (2, 1,12, 4, 4, 0), (2,2,12,NULL,4,0),
  (2, 1,13, 1, 2, 0), (2, 1,13, 2, 1, 0), 
  -- Survey #3 --
  (3, 1,14, 1, 1, 0), (3, 1,14, 2, 2, 0), (3, 1,14, 3, 3, 1), (3, 1,14, 4, 4, 1),
  (3, 1,15, 1, 1, 0), (3, 1,15, 2, 2, 0), (3, 1,15, 3, 3, 1),
  (3, 1,17, 1, 5, 0), (3, 1,17, 2, 6, 0), (3, 1,17, 3, 7, 1),
  (3, 1,18, 1, 5, 0), (3, 1,18, 2, 6, 0), (3, 1,18, 3, 7, 1),
  (3, 1,19, 1, 5, 0), (3, 1,19, 2, 6, 0),
  -- Survey #4 --
  (4, 1,25, 1, 8, 0), (4, 1,25, 2, 9, 0), (4, 1,25, 3,10, 0), (4, 1,25, 4,11, 0),
  (4, 1,28, 1, 8, 0), (4, 1,28, 2, 9, 0), (4, 1,28, 3,10, 0), (4, 1,28, 4,11, 0),
  (4, 1,30, 1, 2, 0), (4, 1,30, 2, 3, 0), (4, 1,30, 3, 4, 0);

