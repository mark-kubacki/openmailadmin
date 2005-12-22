#!/bin/env php
<?php
if(!function_exists('file_put_contents')) {
	function file_put_contents($filename, $contents)
	{
		if($fp = fopen($filename, 'w'))
		{
		fwrite($fp, $contents);
		fclose($fp);
		return true;
		}
		return false;
	}
}

$MTA['virtual']	= '/etc/postfix/db/virtual';
$MTA['regexp']	= '/etc/postfix/db/virtual.regex';
$MTA['domains']	= '/etc/postfix/db/domains';
$PASSWD_CACHE   = '/var/lib/pam_mysql.cache';			// in case you cache your SQL-entries
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
	$result = mysql_query('SELECT address,dest FROM virtual WHERE active=1 ORDER BY address DESC');
	while($row = mysql_fetch_assoc($result)) {
		$fline .= $row['address']."\t\t".$row['dest']."\n";
	}
	mysql_free_result($result);
	file_put_contents($MTA['virtual'], $fline);
	unset($fline);
	mysql_unbuffered_query('UPDATE LOW_PRIORITY IGNORE virtual SET neu=0 WHERE neu=1');
	exec($POSTPROCESS.$MTA['virtual']);
}

// virtual.regexp
$result = mysql_query('SELECT COUNT(*) AS neue FROM virtual_regexp WHERE neu=1');
	$row = mysql_fetch_assoc($result);
	mysql_free_result($result);
if(!is_file($MTA['regexp']) || $row['neue'] > 0 || time()%6<1) {
	$result = mysql_query('SELECT reg_exp,dest FROM virtual_regexp WHERE active=1 ORDER BY LENGTH(reg_exp) DESC');
	while($row = mysql_fetch_assoc($result)) {
		$fline .= $row['reg_exp']."\t\t".$row['dest']."\n";
	}
	mysql_free_result($result);
	file_put_contents($MTA['regexp'], $fline);
	unset($fline);
	mysql_unbuffered_query('UPDATE LOW_PRIORITY IGNORE virtual_regexp SET neu=0 WHERE neu=1');
}

// domains
if(!is_file($MTA['domains']) || time()%48<1) {
	$result = mysql_query('SELECT domain FROM domains order by (SELECT count(*) FROM virtual WHERE address LIKE CONCAT(\'%\', \'@\', domain)) DESC');
	while($row = mysql_fetch_assoc($result)) {
		$fline .= $row['domain']."\t\t".$row['domain']."\n";
	}
	mysql_free_result($result);
	file_put_contents($MTA['domains'], $fline);
	unset($fline);
	exec($POSTPROCESS.$MTA['domains']);
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
