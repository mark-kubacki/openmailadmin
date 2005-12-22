#!/bin/env php
<?php
if(!function_exists('file_put_contents')) {
	function file_put_contents($filename, $contents) {
		if($fp = fopen($filename, 'w')) {
			fwrite($fp, $contents);
			fclose($fp);
			return true;
		}
		return false;
	}
}

function make_hashfile_of_query($file, $query, $postprocess = true) {
	global $POSTPROCESS;
	$fline = '';

	$result = mysql_query($query);
	while($row = mysql_fetch_row($result)) {
		$fline .= $row[0]."\t\t".$row[1]."\n";
	}
	mysql_free_result($result);
	file_put_contents($file, $fline);
	if($postprocess)
		exec($POSTPROCESS.$file);
}

$MTA['virtual']	= '/etc/postfix/db/virtual';
$MTA['regexp']	= '/etc/postfix/db/virtual.regex';
$MTA['domains']	= '/etc/postfix/db/domains';
$PASSWD_CACHE	= '/var/lib/pam_mysql.cache';			// in case you cache your SQL-entries
$POSTPROCESS	= '/usr/sbin/postmap ';

$DB	= array('HOST'	=> 'localhost',
		'USER'	=> 'yourMySQL-User',
		'PASS'	=> '##MysqlSecret-SELECT-only##',
		'DB'	=> 'yourMySQL-DB'
		);
mysql_connect($DB['HOST'], $DB['USER'], $DB['PASS']) or die('Cannot connect to MySQL Server.');
mysql_select_db($DB['DB']) or die('Cannot select Database');

// virtual
$result = mysql_query('SELECT COUNT(*) AS neue FROM virtual WHERE neu=1');
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
if(!is_file($MTA['virtual']) || $row['neue'] > 0 || time()%6<1) {
	make_hashfile_of_query($MTA['virtual'], 'SELECT address,dest FROM virtual WHERE active=1 ORDER BY address DESC');
	mysql_unbuffered_query('UPDATE LOW_PRIORITY IGNORE virtual SET neu=0 WHERE neu=1');
}

// virtual.regexp
$result = mysql_query('SELECT COUNT(*) AS neue FROM virtual_regexp WHERE neu=1');
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
if(!is_file($MTA['regexp']) || $row['neue'] > 0 || time()%6<1) {
	make_hashfile_of_query($MTA['regexp'], 'SELECT reg_exp,dest FROM virtual_regexp WHERE active=1 ORDER BY LENGTH(reg_exp) DESC', false);
	mysql_unbuffered_query('UPDATE LOW_PRIORITY IGNORE virtual_regexp SET neu=0 WHERE neu=1');
}

// domains
if(!is_file($MTA['domains']) || time()%48<1) {
	make_hashfile_of_query($MTA['domains'], 'SELECT domain FROM domains order by (SELECT count(*) FROM virtual WHERE address LIKE CONCAT(\'%\', \'@\', domain)) DESC');
}

// passwd_cache
if($PASSWD_CACHE != null && (!is_file($PASSWD_CACHE) || time()%24<1) && $fp = @fopen($PASSWD_CACHE, 'w')) {
	$result = mysql_query('SELECT mbox, pass_crypt FROM user');
	while($row = mysql_fetch_assoc($result)) {
		fputs($fp, $row['mbox'].':'.$row['pass_crypt']."\n");
	}
	mysql_free_result($result);
	fclose($fp);
}

if(time()%50 < 2)
	mysql_unbuffered_query('OPTIMIZE TABLE virtual, virtual_regexp, user, domains');
mysql_close();
?>
