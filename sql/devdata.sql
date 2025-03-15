delete from tlc_tt_surveys;
insert into tlc_tt_surveys (title) values ('2026 Triple T');
insert into tlc_tt_surveys (title,active,closed) values ('2024 Time and Talent Survey','2024-04-01','2024-06-01');
insert into tlc_tt_surveys (title,active) values ('2025 Trinity Time and Talent Survey','2025-04-01');

delete from tlc_tt_userids;
insert into tlc_tt_userids (userid,fullname,token,password,email,anonid) values
  ('mikemayer67','Mike Mayer','1234567890','$2y$12$yeSmPLkOzyqAoQxtsXwJAOk0TvMn5fkPbh8ndw0b8W4Zy7hC53wEq','mikemayer67@vmwishes.com','$2y$12$CZO1nM3fj4bsRKDigJQzo.IPhCpUjqMpvJ0n5nKUKjPursF5j4EAO');
insert into tlc_tt_userids (userid,fullname,token,password,anonid) values
  ('kitkat15','KitKat Lardo Mayer','1234567890','$2y$12$lFbHBX49Tb9Tr8bPXdr3PO92EqgqKb3VN/AwaIlYS4oo6mP8JNUOG','$2y$12$dHeRZDElHi3KqxnMs8CYTeVZLqZDeYGVU0o2Qqr8DCHieFtewUb9m');
insert into tlc_tt_userids (userid,fullname,token,password,anonid) values
  ('snikers','Snickers KrazyKat Mayer','1234567890','$2y$12$0QvuwzOA0Djtv8JjVNrGEuyBjYqNcyCg.W3UyWedb/XBX4eNOeBL2','$2y$12$290O0qvJp9aBBnm17Xvi7up/.r4CgUAGIjFxsPCk8NICCWYhRHEBS');

delete from tlc_tt_anonids;
insert into tlc_tt_anonids values ('anon_ABCDEFGHIJ');

delete from tlc_tt_reset_tokens;
insert into tlc_tt_reset_tokens (userid,token,expires) values ('mikemayer67','ABCDEFGHI','2025-03-25');
