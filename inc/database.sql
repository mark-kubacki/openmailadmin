CREATE TABLE `domains` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `domain` varchar(64) NOT NULL default '',
  `categories` varchar(100) NOT NULL default 'all',
  `owner` varchar(16) NOT NULL default '',
  `a_admin` tinytext NOT NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `domain` (`domain`),
  KEY `owner` (`owner`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ;

INSERT INTO `domains` VALUES (1, 'example.com', 'all, samples', 'admin', 'admin');

CREATE TABLE `user` (
  `mbox` varchar(16) NOT NULL default '',
  `person` varchar(100) character set utf8 collate utf8_unicode_ci NOT NULL default '',
  `pate` varchar(16) NOT NULL default '',
  `canonical` varchar(100) NOT NULL default '',
  `pass_crypt` varchar(14) default '',
  `pass_md5` varchar(33) NOT NULL default '',
  `reg_exp` varchar(100) NOT NULL default '',
  `domains` varchar(100) NOT NULL default '',
  `active` tinyint(1) NOT NULL default '0',
  `mbox_exists` tinyint(1) NOT NULL default '0',
  `created` int(10) unsigned NOT NULL default '0',
  `last_login` int(10) unsigned NOT NULL default '0',
  `max_alias` int(10) unsigned NOT NULL default '1',
  `max_regexp` int(10) unsigned NOT NULL default '1',
  `a_admin_domains` tinyint(2) NOT NULL default '0',
  `a_admin_user` tinyint(2) NOT NULL default '0',
  `a_super` tinyint(2) NOT NULL default '0',
  PRIMARY KEY  (`mbox`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/* HERE: Modify the passwords!
 *       ##CyrusSecret## has to be the same as in your config.local.inc.php
 *       You will never log in as 'cyrus' - 'admin' is the user for that.
 */
INSERT INTO `user` VALUES ('admin', 'Admin John Doe', 'admin', 'me@example.com', ENCRYPT('admin'), MD5('admin'), '', 'all', 1, 1, NOW(), NOW(), 10000, 100, 2, 2, 2);
INSERT INTO `user` VALUES ('cyrus', 'CYRUS', 'CYRUS', '--@example.com', ENCRYPT('##CyrusSecret##'), MD5('##CyrusSecret##'), '', 'none', 1, 1, NOW(), NOW(), 0, 0, 0, 0, 1);

CREATE TABLE `virtual` (
  `address` varchar(255) NOT NULL default '',
  `dest` text,
  `owner` varchar(16) NOT NULL default '',
  `active` tinyint(1) NOT NULL default '1',
  `neu` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`address`),
  KEY `owner` (`owner`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `virtual` VALUES ('me@example.com', 'admin', 'admin', 1, 1);

CREATE TABLE `virtual_regexp` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `reg_exp` varchar(255) NOT NULL default '',
  `dest` text,
  `owner` varchar(16) NOT NULL default '',
  `active` tinyint(1) NOT NULL default '0',
  `neu` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`ID`),
  KEY `owner` (`owner`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ;

INSERT INTO `virtual_regexp` VALUES (11, '/^(postmaster|abuse|security|root)@example\\.com/', 'admin', 'admin', 1, 1);

CREATE TABLE `imap_demo` (
  `mailbox` varchar(250) NOT NULL default '',
  `used` int(10) unsigned NOT NULL default '0',
  `qmax` int(10) unsigned NOT NULL default '0',
  `ACL` tinytext,
  PRIMARY KEY `mailbox` (`mailbox`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `imap_demo` VALUES ('user.admin', 0, 0, 'admin lrswipcda');
INSERT INTO `imap_demo` VALUES ('shared', 0, 0, 'anyone lrswipcda');
