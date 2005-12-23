#!/bin/env php
<?php
function make_hashfile_of_query($file, $query, $delimiter = "\t\t", $postprocess = true) {
	global $POSTPROCESS, $db;

	if($fp = fopen($file, 'w')) {
		$result = $db->Execute($query);
		while(!$result->EOF) {
			fputs($fp, $result->fields[0].$delimiter.$result->fields[1]."\n");
			$result->MoveNext();
		}
		fclose($fp);

		if($postprocess) {
			exec(sprintf($POSTPROCESS, $file));
		}
	}
}

$MTA['virtual']	= '/etc/postfix/db/virtual';
$MTA['regexp']	= '/etc/postfix/db/virtual.regex';
$MTA['domains']	= '/etc/postfix/db/domains';
$PASSWD_CACHE	= '/var/lib/pam_mysql.cache';			// in case you cache your SQL-entries
$POSTPROCESS	= '/usr/sbin/postmap %s';

$DB	= array('TYPE'	=> 'mysql',
		'HOST'	=> 'localhost',
		'USER'	=> 'yourMySQL-User',
		'PASS'	=> '##MysqlSecret-SELECT-only##',
		'DB'	=> 'yourMySQL-DB',
		'PREFIX'=> '',
		);

include('adodb/adodb.inc.php');
$db	= ADONewConnection($DB['TYPE']);
$db->Connect($DB['HOST'], $DB['USER'], $DB['PASS'], $DB['DB']) or die('Cannot connect to MySQL Server.');
$db->SetFetchMode(ADODB_FETCH_NUM);

// virtual
$amount_new	= $db->GetOne('SELECT COUNT(*) FROM '.$DB['PREFIX'].'virtual WHERE neu=1');
if(!is_file($MTA['virtual']) || $amount_new > 0 || time()%6<1) {
	make_hashfile_of_query($MTA['virtual'], 'SELECT address,dest FROM '.$DB['PREFIX'].'virtual WHERE active=1 ORDER BY address DESC');
	$db->Execute('UPDATE LOW_PRIORITY IGNORE '.$DB['PREFIX'].'virtual SET neu=0 WHERE neu=1');
}

// virtual.regexp
$amount_new	= $db->GetOne('SELECT COUNT(*) FROM '.$DB['PREFIX'].'virtual_regexp WHERE neu=1');
if(!is_file($MTA['regexp']) || $amount_new > 0 || time()%6<1) {
	make_hashfile_of_query($MTA['regexp'], 'SELECT reg_exp,dest FROM '.$DB['PREFIX'].'virtual_regexp WHERE active=1 ORDER BY LENGTH(reg_exp) DESC', "\t\t", false);
	$db->Execute('UPDATE LOW_PRIORITY IGNORE '.$DB['PREFIX'].'virtual_regexp SET neu=0 WHERE neu=1');
}

// domains
if(!is_file($MTA['domains']) || time()%48<1) {
	make_hashfile_of_query($MTA['domains'], 'SELECT domain FROM '.$DB['PREFIX'].'domains ORDER BY (SELECT count(*) FROM '.$DB['PREFIX'].'virtual WHERE address LIKE CONCAT(\'%\', \'@\', domain)) DESC');
}

// passwd_cache
if(!is_null($PASSWD_CACHE) && (!is_file($PASSWD_CACHE) || time()%24<1)) {
	make_hashfile_of_query($PASSWD_CACHE, 'SELECT mbox, pass_crypt FROM '.$DB['PREFIX'].'user', ':', false);
}

if(time()%50 < 2 && ($DB['TYPE'] == 'mysql' || $DB['TYPE'] == 'mysqli')) {
	$db->Execute('OPTIMIZE TABLE '.$DB['PREFIX'].'virtual, '.$DB['PREFIX'].'virtual_regexp, '.$DB['PREFIX'].'user, '.$DB['PREFIX'].'domains');
}
?>