<?php
// repeat these lines for every server or virtual domain
$cfg['Servers']['verbose'][] = 'localhost';
$cfg['Servers']['number'][] = $i++;
$cfg['Servers']['IMAP'][] = array(
	'TYPE'	=> 'fake-imap',
	'HOST'	=> 'localhost',
	'PORT'	=> 143,
	'ADMIN'	=> 'cyrus',
	'PASS'	=> '##secret##',
);
$cfg['Servers']['DB'][] = array(
	'DSN'		=> 'mysql://user:pass@host/db',
	'PREFIX'	=> '',
);
?>