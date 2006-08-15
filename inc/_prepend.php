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
include('./inc/lib/OMAExceptions.php');

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
		'vdomains'	=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'vdomains',
		'vdom_admins'	=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'vdom_admins',
		'domains'	=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'domains',
		'domain_admins'	=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'domain_admins',
		'virtual'	=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'virtual',
		'virtual_regexp'=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'virtual_regexp',
		'imap_demo'	=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'imap_demo'
		);
// Objects' initialization
User::$db		= $db;
User::$tablenames	= $cfg['tablenames'];
Domain::$db		= $db;
Domain::$tablenames	= $cfg['tablenames'];
IMAPVirtualDomain::$db	= $db;
IMAPVirtualDomain::$tablenames	= $cfg['tablenames'];
AEmailMapperModel::$db		= $db;
AEmailMapperModel::$tablenames	= $cfg['tablenames'];
Address::$db		= $db;
Address::$tablenames	= $cfg['tablenames'];
RegexpAddress::$db		= $db;
RegexpAddress::$tablenames	= $cfg['tablenames'];

// IMAP
$imap = IMAP_get_instance($cfg['Servers']['IMAP'][$_SESSION['server']],
			$cfg['Servers']['IMAP'][$_SESSION['server']]['TYPE']);

// include the backend
$oma	= new openmailadmin($db, $cfg['tablenames'], $cfg, $imap);
$oma->authenticated_user	= $authinfo;
unset($authinfo);
$ErrorHandler	= ErrorHandler::getInstance();

// Query for the current user...
if(!(isset($_GET['cuser']) && $_GET['cuser'] != $oma->authenticated_user->ID)) {
	$oma->current_user	= $oma->authenticated_user;
} else try {
	$oma->current_user	= User::get_by_ID($_GET['cuser']);
	if(!($oma->authenticated_user->is_superuser()
	   || User::is_descendant($oma->current_user, $oma->authenticated_user))) {
		throw new Exception(txt(2));
	}
} catch (Exception $e) {
	error($e->getMessage());
	include('./templates/'.$cfg['theme'].'/common-footer_nv.tpl');
	exit();
}

// ... and his paten.
$cpate = $oma->current_user->get_pate();

// Display navigation menu.
$arr_navmenu = $oma->get_menu();
include('./templates/'.$cfg['theme'].'/navigation/navigation.tpl');

?>
