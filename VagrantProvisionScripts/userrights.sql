-- mysql_secure_installation gives problems with custom located mariadb datadir, so do the secure installation ourselves
-- set_root_password
UPDATE mysql.user SET Password=PASSWORD('roo4') WHERE User='root';
-- remove_anonymous_users
DELETE FROM mysql.user WHERE User='';
-- remove_remote_root
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
-- remove_test_database
DROP DATABASE test;
-- Removing privileges on test database.
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
-- Development: give root access from outside as well
GRANT ALL ON *.* TO 'root'@'192.168.33.%' IDENTIFIED BY 'roo4' WITH GRANT OPTION;

-- Create database telegrambots and give access
CREATE DATABASE `telegrambots`;
GRANT USAGE ON *.* TO 'telegrambot'@'localhost' IDENTIFIED BY 'telegrambot';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES, CREATE VIEW, EVENT, TRIGGER,
SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EXECUTE ON `telegrambots`.* TO 'telegrambot'@'localhost';

FLUSH PRIVILEGES;
