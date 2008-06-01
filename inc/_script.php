<?php
/**
 * \brief	Include this as small-footsize initialization for/in your
 * 		applications or command-line tools.
 *
 * Please feel free to ask for help on the mailing lists.
 */

ini_set('log_errors', '0');
ini_set('display_errors', '1');
error_reporting(E_ALL);
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'shell';

include('./inc/config.inc.php');
@(include('./inc/config.local.inc.php'))
	or die('You have to create an configuration file, first. Try <a href="setup.php">setup.php</a>.');
include('./inc/translation.inc.php');
include('adodb/adodb.inc.php');
include('./inc/functions.inc.php');

if(!isset($cfg['Servers']['IMAP'][$server_no]['TYPE'])) {
	die('You have forgotten to set TYPEs in the configuration files!');
}

// table names with prefixes
$cfg['tablenames'] = array();
foreach(array('user', 'domains', 'virtual', 'virtual_regexp', 'imap_demo') as $table) {
	$cfg['tablenames'][$table] = $cfg['Servers']['DB'][$server_no]['PREFIX'].$table;
}

// Objects' initialization
$db			= ADONewConnection($cfg['Servers']['DB'][$server_no]['DSN'])
				or die('Cannot connect to database server.');
User::$db		= $db;
User::$tablenames	= $cfg['tablenames'];

// IMAP
$imap = IMAP_get_instance($cfg['Servers']['IMAP'][$server_no],
			$cfg['Servers']['IMAP'][$server_no]['TYPE']);

// include the backend
$oma	= new openmailadmin($db, $cfg['tablenames'], $cfg, $imap);
$oma->authenticated_user	= User::authenticate($creator_mbox, $creator_passwd);
$oma->current_user		= $oma->authenticated_user;
$ErrorHandler	= ErrorHandler::getInstance();

?>