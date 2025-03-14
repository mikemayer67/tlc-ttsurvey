delete from tlc_tt_surveys;
insert into tlc_tt_surveys (title) values ('2026 Triple T');
insert into tlc_tt_surveys (title,active,closed) values ('2024 Time and Talent Survey','2024-04-01','2024-06-01');
insert into tlc_tt_surveys (title,active) values ('2025 Trinity Time and Talent Survey','2025-04-01');

delete from tlc_tt_userids;
insert into tlc_tt_userids (userid,fullname,token,password,email) values
  ('mikemayer67','Mike Mayer','1234567890','not really','mikemayer67@vmwishes.com');
insert into tlc_tt_userids (userid,fullname,token,password) values
  ('kitkat15','KitKat Lardo Mayer','1234567890','not really');
insert into tlc_tt_userids (userid,fullname,token,password) values
  ('snikers','Snickers KrazyKat Mayer','1234567890','not really');

delete from tlc_tt_reset_tokens;
insert into tlc_tt_reset_tokens (userid,token,expires) values ('mikemayer67','ABCDEFGHI','2025-03-25');
