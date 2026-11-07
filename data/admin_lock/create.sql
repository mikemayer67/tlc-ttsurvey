select * from tlc_srv_settings where name='admin-lock';

insert into  tlc_srv_settings (name,value)
values (
  'admin-lock',
  concat('ABCDEFGHI|',  unix_timestamp() + 600,  '|Test User')
  )
on duplicate key update value=values(value);