#!/bin/env php
<?php
/* *********************** change these according to your environment ******* */
$MTA['virtual']	= '/etc/postfix/db/virtual';
$MTA['regexp']	= '/etc/postfix/db/virtual.regex';
$MTA['domains']	= '/etc/postfix/db/domains';
$POSTPROCESS	= '/usr/sbin/postmap %s';
// Set this to null if you don't use pam_pwdfile for caching.
$PASSWD_CACHE	= '/var/lib/pam_mysql.cache';

$DB = array(
	'DSN'		=> 'mysqli://User:Passwd@localhost/DB',
	'PREFIX'	=> '',
);

/* *********************** functions **************************************** */
/* *********************** don't edit anything below +++++++++++++++++******* */
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

/* *********************** logic ******************************************** */
include('adodb/adodb.inc.php');
$db	= ADONewConnection($DB['DSN']);
$db->SetFetchMode(ADODB_FETCH_NUM);

// virtual
$amount_new	= $db->GetOne('SELECT COUNT(*) FROM '.$DB['PREFIX'].'virtual WHERE neu=1');
if(!is_file($MTA['virtual']) || $amount_new > 0 || time()%6<1) {
	make_hashfile_of_query($MTA['virtual'], 'SELECT address,dest FROM '.$DB['PREFIX'].'virtual WHERE active=1 ORDER BY address DESC');
	$db->Execute('UPDATE '.$DB['PREFIX'].'virtual SET neu=0 WHERE neu=1');
}

// virtual.regexp
$amount_new	= $db->GetOne('SELECT COUNT(*) FROM '.$DB['PREFIX'].'virtual_regexp WHERE neu=1');
if(!is_file($MTA['regexp']) || $amount_new > 0 || time()%6<1) {
	make_hashfile_of_query($MTA['regexp'], 'SELECT reg_exp,dest FROM '.$DB['PREFIX'].'virtual_regexp WHERE active=1 ORDER BY LENGTH(reg_exp) DESC', "\t\t", false);
	$db->Execute('UPDATE '.$DB['PREFIX'].'virtual_regexp SET neu=0 WHERE neu=1');
}

// domains
$amount_new	= $db->GetOne('SELECT COUNT(*) FROM '.$DB['PREFIX'].'domains WHERE neu=1');
if(!is_file($MTA['domains']) || $amount_new > 0 || time()%96<1) {
	make_hashfile_of_query($MTA['domains'], 'SELECT domain,domain FROM '.$DB['PREFIX'].'domains');
	$db->Execute('UPDATE '.$DB['PREFIX'].'domains SET neu=0 WHERE neu=1');
}

// for pam_pwdfile
if(!is_null($PASSWD_CACHE) && (!is_file($PASSWD_CACHE) || time()%24<1)) {
	make_hashfile_of_query($PASSWD_CACHE, 'SELECT mbox, password FROM '.$DB['PREFIX'].'user', ':', false);
}

// optimize DB
if(time()%50 < 2 && ($DB['TYPE'] == 'mysql' || $DB['TYPE'] == 'mysqli')) {
	$db->Execute('OPTIMIZE TABLE '.$DB['PREFIX'].'virtual, '.$DB['PREFIX'].'virtual_regexp, '.$DB['PREFIX'].'user, '.$DB['PREFIX'].'domains');
}
?>