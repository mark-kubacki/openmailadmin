<?php
ob_start('ob_gzhandler');
// For security reasons error messages should not be displayed.
ini_set('log_errors', '1');
ini_set('display_errors', '0');
error_reporting(E_ALL);

include('./inc/config.inc.php');
@(include('./inc/config.local.inc.php'))
	or die('You have to create an configuration file, first. Try <a href="setup.php">setup.php</a>.');
include('./inc/translation.inc.php');
if($cfg['show_exceptions_online']) {
	include('./inc/exception_handler.php');
}
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
header('Content-type: text/html; charset=utf-8');
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
// Objects' initialization
User::$db		= $db;
User::$tablenames	= $cfg['tablenames'];

// IMAP
$imap = IMAP_get_instance($cfg['Servers']['IMAP'][$_SESSION['server']],
			$cfg['Servers']['IMAP'][$_SESSION['server']]['TYPE']);

// include the backend
$oma	= new openmailadmin($db, $cfg['tablenames'], $cfg, $imap);
$oma->authenticated_user	= $authinfo;
unset($authinfo);
$ErrorHandler	= ErrorHandler::getInstance();

// Query for the current user...
if(!(isset($_GET['cuser']) && $_GET['cuser'] != $oma->authenticated_user->mbox)) {
	$oma->current_user	= $oma->authenticated_user;
} else try {
	$oma->current_user	= new User($_GET['cuser']);
	if(!($oma->authenticated_user->is_superuser()
	   || $oma->current_user->pate == $oma->authenticated_user->mbox
	   || $oma->user_is_descendant($oma->current_user->mbox, $oma->authenticated_user->mbox))) {
		throw new Exception(txt(2));
	}
} catch (Exception $e) {
	error($e->getMessage());
	include('./templates/'.$cfg['theme'].'/common-footer_nv.tpl');
	exit();
}

// ... and his paten.
if($oma->current_user->mbox == $oma->current_user->pate) {
	$cpate = $oma->current_user;
} else {
	try {
		$cpate = new User($oma->current_user->pate);
	} catch (Exception $e) {
		$cpate = $oma->current_user;
	}
}

// Display navigation menu.
$arr_navmenu = array();
	$arr_navmenu[]	= array('link'		=> 'index.php'.($oma->current_user->mbox != $oma->authenticated_user->mbox ? '?cuser='.$oma->current_user->mbox : ''),
					'caption'	=> txt('1'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'index.php'));
if($oma->current_user->max_alias > 0 || $oma->authenticated_user->a_super >= 1 || $oma->user_get_used_alias($oma->current_user->mbox)) {
	$arr_navmenu[]	= array('link'		=> 'addresses.php'.($oma->current_user->mbox != $oma->authenticated_user->mbox ? '?cuser='.$oma->current_user->mbox : ''),
					'caption'	=> txt('17'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'addresses.php'));
}
if($oma->current_user->mbox == $oma->authenticated_user->mbox) {
	$arr_navmenu[]	= array('link'		=> 'folders.php'.($oma->current_user->mbox != $oma->authenticated_user->mbox ? '?cuser='.$oma->current_user->mbox : ''),
					'caption'	=> txt('103'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'folders.php'));
}
if($oma->current_user->max_regexp > 0 || $oma->authenticated_user->a_super >= 1 || $oma->user_get_used_regexp($oma->current_user->mbox)) {
	$arr_navmenu[]	= array('link'		=> 'regexp.php'.($oma->current_user->mbox != $oma->authenticated_user->mbox ? '?cuser='.$oma->current_user->mbox : ''),
					'caption'	=> txt('33'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'regexp.php'));
}
if($oma->authenticated_user->a_admin_domains >= 1 || $oma->user_get_number_domains($oma->current_user->mbox) > 0) {
	$arr_navmenu[]	= array('link'		=> 'domains.php'.($oma->current_user->mbox != $oma->authenticated_user->mbox ? '?cuser='.$oma->current_user->mbox : ''),
					'caption'	=> txt('54'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'domains.php'));
}
if($oma->authenticated_user->a_admin_user >= 1 || $oma->user_get_number_mailboxes($oma->current_user->mbox) > 0) {
	$arr_navmenu[]	= array('link'		=> 'mailboxes.php'.($oma->current_user->mbox != $oma->authenticated_user->mbox ? '?cuser='.$oma->current_user->mbox : ''),
					'caption'	=> txt('79'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'mailboxes.php'));
}
include('./templates/'.$cfg['theme'].'/navigation/navigation.tpl');

?>
