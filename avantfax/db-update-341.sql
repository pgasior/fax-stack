-- Database update for 3.4.1

alter table UserAccount change password password VARCHAR(255) NOT NULL;
alter table UserPasswords change pwdhash pwdhash VARCHAR(255) NOT NULL;
