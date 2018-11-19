CREATE DATABASE mogboard;
CREATE USER mogboard@localhost IDENTIFIED BY 'mogboard';
GRANT ALL PRIVILEGES ON *.* TO mogboard@'%' IDENTIFIED BY 'mogboard';
GRANT ALL PRIVILEGES ON *.* TO mogboard@localhost IDENTIFIED BY 'mogboard';
FLUSH PRIVILEGES;
