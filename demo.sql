


insert into nodes values ('n1', 'n1', 'application', 'service', 1, '', current_timestamp, current_timestamp);
insert into nodes values ('n2', 'n2', 'application', 'service', 1, '', current_timestamp, current_timestamp);
insert into nodes values ('n3', 'n3', 'application', 'service', 1, '', current_timestamp, current_timestamp);
insert into nodes values ('n4', 'n4', 'application', 'service', 1, '', current_timestamp, current_timestamp);
insert into nodes values ('n5', 'n5', 'application', 'service', 1, '', current_timestamp, current_timestamp);
insert into edges values ('e1-2', 'n1', 'n2', 'connects_to', '', current_timestamp, current_timestamp);
insert into edges values ('e3-4', 'n3', 'n4', 'connects_to', '', current_timestamp, current_timestamp);
insert into edges values ('e2-5', 'n2', 'n5', 'connects_to', '', current_timestamp, current_timestamp);
insert into edges values ('e4-5', 'n4', 'n5', 'connects_to', '', current_timestamp, current_timestamp);