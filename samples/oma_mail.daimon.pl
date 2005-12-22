#!/bin/env perl -w
use strict;

use DBI;

################ modify these ##################################################
my %MTA	= ( 'virtual'	=> '/etc/postfix/db/virtual',
	'regexp'	=> '/etc/postfix/db/virtual.regex',
	'domains'	=> '/etc/postfix/db/domains' );
my $passwd_cache	= '/var/lib/pam_mysql.cache';
my $postprocess		= '/usr/sbin/postmap ';

my %DB = (  'TYPE'	=> 'mysql',
	'HOST'	=> 'localhost',
	'USER'	=> 'your-MySQL-User',
	'PASS'	=> '##MysqlSecret-SELECT-only##',
	'DB'	=> 'yourMySQL-DB' );

################ no need for modifying anything below ##########################
my $dbh;
my $sth;

$dbh = DBI->connect('DBI:'.$DB{'TYPE'}.':'.$DB{'DB'}.':'.$DB{'HOST'}, $DB{'USER'}, $DB{'PASS'})
	|| die 'Cannot connect to database.';

if(! -e $MTA{'virtual'} || rand(6)<1 || amountOfNewEntries('virtual') > 0) {
	writeFile('virtual', 'virtual', 'address', 'dest', 'address DESC');
}
if(! -e $MTA{'regexp'} || rand(6)<1 || amountOfNewEntries('virtual_regexp') > 0) {
	writeFile('regexp', 'virtual_regexp', 'reg_exp', 'dest', 'LENGTH(reg_exp) DESC');
}
if(! -e $MTA{'domains'} || rand(48)<1) {
	writeDomains();
}
if(defined $passwd_cache && (!(-e $passwd_cache) || rand(24)<1)) {
	writePasswdCache();
}

if(rand(25) < 1) {
	$dbh->do(q{OPTIMIZE TABLE virtual, virtual_regexp, user, domains});
}

$dbh->disconnect;

################################################################################
sub writePasswdCache {
################################################################################
	my @row;

	$sth = $dbh->prepare(q{
		SELECT mbox, pass_crypt
		FROM user
		});
	$sth->execute();

	open(OUT, '>', $passwd_cache);
	while(@row = $sth->fetchrow_array()) {
		print OUT $row[0].':'.$row[1]."\n";
	}
	close OUT;
}

################################################################################
sub writeDomains {
################################################################################
	my @row;

	$sth = $dbh->prepare(q{
		SELECT domain
		FROM domains
		ORDER BY (SELECT count(*)
			  FROM virtual
			  WHERE address LIKE CONCAT('%@', domain))
			 DESC
		});
	$sth->execute();

	open(OUT, '>', $MTA{'domains'});
	while(@row = $sth->fetchrow_array()) {
		print OUT $row[0]."\t\t".$row[0]."\n";
	}
	close OUT;
	system($postprocess.$MTA{'domains'});
}

################################################################################
sub writeFile {
################################################################################
	my @row;

	$sth = $dbh->prepare(qq{
		SELECT $_[2], $_[3]
		FROM $_[1]
		WHERE active=1 ORDER BY $_[4]
		});
	$sth->execute();

	open(OUT, '>', $MTA{$_[0]});
	while(@row = $sth->fetchrow_array()) {
		print OUT $row[0]."\t\t".$row[1]."\n";
	}
	close OUT;

	$dbh->do(qq{UPDATE LOW_PRIORITY IGNORE $_[1] SET neu=0 WHERE neu=1});
	system($postprocess.$MTA{$_[0]});
}

################################################################################
sub amountOfNewEntries {
################################################################################
	my $row;

	$sth = $dbh->prepare(qq{
		SELECT COUNT(*) AS neue
		FROM $_[0]
		WHERE neu=1
		});
	$sth->execute();

	$row = $sth->fetchrow_hashref();
	$sth->finish();
	return $row->{'neue'};
}
