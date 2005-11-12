<?php
// repeat these lines for every server or virtual domain
$cfg['Servers']['verbose'][] = 'localhost';
$cfg['Servers']['number'][] = $i++;
$cfg['Servers']['IMAP'][] = array(
	'TYPE'	=> 'fake-imap',		// or fake-imap or courier...
	'HOST'	=> 'localhost',
	'PORT'	=> 143,
	'ADMIN'	=> 'cyrus',
	'PASS'	=> '##CyrusSecret##',
	'VDOM'	=> ''
);
$cfg['Servers']['DB'][] = array(
	'TYPE'	=> 'mysql',		// currently only mysql
	'HOST'	=> 'localhost',
	'USER'	=> 'yourMySQL-User',
	'PASS'	=> 'yourMySQL-Passwd',
	'DB'	=> 'yourMySQL-DB',
	'PREFIX'	=> ''
);
?>