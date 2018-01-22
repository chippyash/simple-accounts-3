use mysql;
create database if not exists test;
create user 'test'@'localhost' identified by 'test';
# grant all PRIVILEGES on test.* to 'test'@'localhost';
grant SUPER on *.* to 'test'@'localhost';
flush privileges;