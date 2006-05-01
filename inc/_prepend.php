<?php
ob_start('ob_gzhandler');
// For security reasons error messages should not be displayed.
ini_set('log_errors', '1');
ini_set('display_errors', '0');
// error_reporting(E_ALL ^ E_NOTICE);
error_reporting(E_ALL);

include('./inc/config.inc.php');
@(include('./inc/config.local.inc.php'))
	or die('You have to create an configuration file, first. Try <a href="setup.php">setup.php</a>.');
include('./inc/translation.inc.php');
include('adodb/adodb.inc.php');
include('./inc/functions.inc.php');

if(is_readable('./templates/'.$cfg['theme'].'/__aux.php')) {
	include('./templates/'.$cfg['theme'].'/__aux.php');
}

// Initialization
	$input	= new HTMLInputTagGenerator();

	if(isset($cfg['max_elements_per_page']))
		$amount_set 	= array_unique(array('10', '25', '50', '100', '--', $cfg['max_elements_per_page']));
	else
		$amount_set 	= array('10', '25', '50', '100', '--');

// MAIN
header('Content-type: text/html; charset='.$encoding);
include('./templates/'.$cfg['theme'].'/common-header.tpl');

// Authentification
include('./inc/miniauth.inc.php');

if(!isset($cfg['Servers']['IMAP'][$_SESSION['server']]['TYPE'])) {
	die('You have forgotten to set TYPEs in the configuration files!');
}

// table names with prefixes
$cfg['tablenames']
	= array('user'		=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'user',
		'domains'	=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'domains',
		'virtual'	=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'virtual',
		'virtual_regexp'=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'virtual_regexp',
		'imap_demo'	=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'imap_demo'
		);
$now_on = isset($_GET['cuser']) ? $_GET['cuser'] : $authinfo['mbox'];

// IMAP
switch($cfg['Servers']['IMAP'][$_SESSION['server']]['TYPE']) {
	case 'fake-imap':
		$imap = new Fake_IMAP($cfg['Servers']['IMAP'][$_SESSION['server']], $db, $cfg['tablenames']);
		break;
	default:
		$imap = new Cyrus_IMAP($cfg['Servers']['IMAP'][$_SESSION['server']]);
		break;
}

// include the backend
$oma	= new openmailadmin($db, $cfg['tablenames'], $cfg, $imap);
$oma->authenticated_user	= &$authinfo;
$oma->current_user		= &$cuser;
$ErrorHandler	= ErrorHandler::getInstance();
unset($authinfo);
unset($cuser);

// Query for the current user...
$result = $db->GetRow('SELECT * FROM '.$cfg['tablenames']['user'].' WHERE mbox='.$db->qstr($now_on));
if(!$result === false) {
	$oma->current_user = $result;
	if(!($oma->authenticated_user['a_super'] >= 1
	   || $oma->current_user['mbox'] == $oma->authenticated_user['mbox']
	   || $oma->current_user['pate'] == $oma->authenticated_user['mbox']
	   || $oma->user_is_descendant($oma->current_user['mbox'], $oma->authenticated_user['mbox']))) {
		error(txt('2'));
		include('./templates/'.$cfg['theme'].'/common-footer_nv.tpl');
		exit();
	}
} else {
	error(txt('2'));
	exit();
}

// ... and his paten.
if($oma->current_user['mbox'] == $oma->current_user['pate']) {
	$cpate = array('person' => txt('29'), 'mbox' => $oma->current_user['mbox']);
} else {
	$cpate = $db->GetRow('SELECT person, mbox FROM '.$cfg['tablenames']['user'].' WHERE mbox='.$db->qstr($oma->current_user['pate']));
	if($cpate === false) {
		$cpate	= array('person' => txt('28'), 'mbox' => $oma->current_user['mbox']);
	}
}

// Display navigation menu.
$arr_navmenu = array();
	$arr_navmenu[]	= array('link'		=> 'index.php'.($oma->current_user['mbox'] != $oma->authenticated_user['mbox'] ? '?cuser='.$oma->current_user['mbox'] : ''),
					'caption'	=> txt('1'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'index.php'));
if($oma->current_user['max_alias'] > 0 || $oma->authenticated_user['a_super'] >= 1 || $oma->user_get_used_alias($oma->current_user['mbox'])) {
	$arr_navmenu[]	= array('link'		=> 'addresses.php'.($oma->current_user['mbox'] != $oma->authenticated_user['mbox'] ? '?cuser='.$oma->current_user['mbox'] : ''),
					'caption'	=> txt('17'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'addresses.php'));
}
if($oma->current_user['mbox'] == $oma->authenticated_user['mbox']) {
	$arr_navmenu[]	= array('link'		=> 'folders.php'.($oma->current_user['mbox'] != $oma->authenticated_user['mbox'] ? '?cuser='.$oma->current_user['mbox'] : ''),
					'caption'	=> txt('103'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'folders.php'));
}
if($oma->current_user['max_regexp'] > 0 || $oma->authenticated_user['a_super'] >= 1 || $oma->user_get_used_regexp($oma->current_user['mbox'])) {
	$arr_navmenu[]	= array('link'		=> 'regexp.php'.($oma->current_user['mbox'] != $oma->authenticated_user['mbox'] ? '?cuser='.$oma->current_user['mbox'] : ''),
					'caption'	=> txt('33'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'regexp.php'));
}
if($oma->authenticated_user['a_admin_domains'] >= 1 || $oma->user_get_number_domains($oma->current_user['mbox']) > 0) {
	$arr_navmenu[]	= array('link'		=> 'domains.php'.($oma->current_user['mbox'] != $oma->authenticated_user['mbox'] ? '?cuser='.$oma->current_user['mbox'] : ''),
					'caption'	=> txt('54'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'domains.php'));
}
if($oma->authenticated_user['a_admin_user'] >= 1 || $oma->user_get_number_mailboxes($oma->current_user['mbox']) > 0) {
	$arr_navmenu[]	= array('link'		=> 'mailboxes.php'.($oma->current_user['mbox'] != $oma->authenticated_user['mbox'] ? '?cuser='.$oma->current_user['mbox'] : ''),
					'caption'	=> txt('79'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'mailboxes.php'));
}
include('./templates/'.$cfg['theme'].'/navigation/navigation.tpl');

?>