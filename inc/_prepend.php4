<?php
$version = '2005-09-05';
ob_start('ob_gzhandler');
// For security reasons error messages should not be displayed.
ini_set('log_errors', '1');
ini_set('display_errors', '0');
// error_reporting(E_ALL ^ E_NOTICE);
error_reporting(E_ALL);

include('config.inc.php4');
	if(is_readable('config.local.inc.php4')) {
	    include('config.local.inc.php4');
	}
	else if(is_readable('inc/config.local.inc.php4')) {
	    include('inc/config.local.inc.php4');
	}
	else {
	    die('You have to create an configuration file, first.');
	}
include('translation.inc.php4');
include('format_shadow_classes.inc.php4');
include('functions.inc.php4');

// Initialisation
	$table	= new _table_shadow();
	$tbled	= new _table();
	$input	= new _input();
	$table->images_dir	= $cfg['images_dir'];
	$tbled->images_dir	= $cfg['images_dir'];
	$tbled->arrProperties['td']	= array('class'	=> 'ed');

	if($cfg['max_elements_per_page'])
	    $amount_set 	= array_unique(array('10', '25', '50', '100', '--', $cfg['max_elements_per_page']));
	else
	    $amount_set 	= array('10', '25', '50', '100', '--');

// MAIN
header('Content-type: text/html; charset='.$encoding);
include('templates/'.$cfg['theme'].'/common-header.tpl');

// Authentification
include('miniauth.inc.php4');

if (!(isset($cfg['Servers']['IMAP'][$_SESSION['server']]['TYPE'])
	&& isset($cfg['Servers']['DB'][$_SESSION['server']]['TYPE']))) {
	die('You have forgotten to set TYPEs in the configuration files!');
}

switch($cfg['Servers']['IMAP'][$_SESSION['server']]['TYPE']) {
    case 'fake-imap':		include('lib/fake-cyradm.php');	break;
    default:			include('lib/cyradm.php');	break;
}

// table names with prefixes
$cfg['tablenames']
	= array('user'		=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'user',
		'domains'	=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'domains',
		'virtual'	=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'virtual',
		'virtual_regexp'=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'virtual_regexp',
		'imap_demo'	=> $cfg['Servers']['DB'][$_SESSION['server']]['PREFIX'].'imap_demo'
		);
$now_on = isset($_GET['cuser']) ? mysql_escape_string($_GET['cuser']) : $authinfo['mbox'];

// and query for the current user
$result = mysql_query('SELECT * FROM '.$cfg['tablenames']['user'].' WHERE mbox=\''.$now_on.'\' LIMIT 1');
if(mysql_num_rows($result) > 0) {
    $cuser = mysql_fetch_assoc($result);
    mysql_free_result($result);

    if(!($authinfo['a_super'] >= 1 || $cuser['mbox'] == $authinfo['mbox']
	|| $cuser['pate'] == $authinfo['mbox'] || IsDescendant($cuser['mbox'], $authinfo['mbox']))) {
	error(txt('2'));
	include('templates/'.$cfg['theme'].'/common-footer_nv.tpl');
	exit();
    }
}
else {
    error(txt('2'));
    exit();
}

// ... and his paten
if($cuser['mbox'] == $cuser['pate']) {
    $cpate = array('person' => txt('29'), 'mbox' => $cuser['mbox']);
}
else {
    $result = mysql_query('SELECT person, mbox FROM '.$cfg['tablenames']['user'].' WHERE mbox=\''.$cuser['pate'].'\' LIMIT 1');
    if(mysql_num_rows($result) > 0) {
	$cpate = mysql_fetch_assoc($result);
	mysql_free_result($result);
    }
    else {
	$cpate	= array('person' => txt('28'), 'mbox' => $cuser['mbox']);
    }
}

// include the backend
include('lib/openmailadmin.php');
$oma 	= new openmailadmin();
$oma->authenticated_user 	= &$authinfo;
$oma->current_user 		= &$cuser;

// Display navigation menu.
$arr_navmenu = array();
    $arr_navmenu[]	= array('link'		=> 'index.php4'.($cuser['mbox'] != $authinfo['mbox'] ? '?cuser='.$cuser['mbox'] : ''),
				'caption'	=> txt('1'),
				'active'	=> stristr($_SERVER['PHP_SELF'], 'index.php4'));
if($cuser['max_alias'] > 0 || $authinfo['a_super'] >= 1 || hsys_getUsedAlias($cuser['mbox'])) {
    $arr_navmenu[]	= array('link'		=> 'addresses.php4'.($cuser['mbox'] != $authinfo['mbox'] ? '?cuser='.$cuser['mbox'] : ''),
				'caption'	=> txt('17'),
				'active'	=> stristr($_SERVER['PHP_SELF'], 'addresses.php4'));
}
if($cuser['mbox'] == $authinfo['mbox']) {
    $arr_navmenu[]	= array('link'		=> 'folders.php4'.($cuser['mbox'] != $authinfo['mbox'] ? '?cuser='.$cuser['mbox'] : ''),
				'caption'	=> txt('103'),
				'active'	=> stristr($_SERVER['PHP_SELF'], 'folders.php4'));
}
if($cuser['max_regexp'] > 0 || $authinfo['a_super'] >= 1 || hsys_getUsedRegexp($cuser['mbox'])) {
    $arr_navmenu[]	= array('link'		=> 'regexp.php4'.($cuser['mbox'] != $authinfo['mbox'] ? '?cuser='.$cuser['mbox'] : ''),
				'caption'	=> txt('33'),
				'active'	=> stristr($_SERVER['PHP_SELF'], 'regexp.php4'));
}
if($authinfo['a_admin_domains'] >= 1 || hsys_n_Domains($cuser['mbox']) > 0) {
    $arr_navmenu[]	= array('link'		=> 'domains.php4'.($cuser['mbox'] != $authinfo['mbox'] ? '?cuser='.$cuser['mbox'] : ''),
				'caption'	=> txt('54'),
				'active'	=> stristr($_SERVER['PHP_SELF'], 'domains.php4'));
}
if($authinfo['a_admin_user'] >= 1 || hsys_n_Mailboxes($cuser['mbox']) > 0) {
    $arr_navmenu[]	= array('link'		=> 'mailboxes.php4'.($cuser['mbox'] != $authinfo['mbox'] ? '?cuser='.$cuser['mbox'] : ''),
				'caption'	=> txt('79'),
				'active'	=> stristr($_SERVER['PHP_SELF'], 'mailboxes.php4'));
}
include('templates/'.$cfg['theme'].'/navigation/navigation.tpl');

?>