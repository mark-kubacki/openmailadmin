<?php
//	Mini-Auth
ini_set('session.use_only_cookies', 1);
session_start();

// authentification
if(isset($_GET['login']) && $_GET['login'] == 'change') {
	session_destroy();
	$_SESSION = array();
} else if(isset($_POST['frm']) && $_POST['frm'] == 'login' && trim($_POST['mboxname']) != '') {
	if(!(isset($_POST['server']) && is_numeric($_POST['server'])))
		$_POST['server'] = 0;
	$db	= ADONewConnection($cfg['Servers']['DB'][$_POST['server']]['DSN'])
		or die('Cannot connect to MySQL Server.');
	$db->SetFetchMode(ADODB_FETCH_ASSOC);

	User::$db		= $db;
	User::$tablenames	= array('user' => $cfg['Servers']['DB'][$_POST['server']]['PREFIX'].'user');

	try {
		$authinfo = User::authenticate($_POST['mboxname'], $_POST['password']);
		unset($_POST['password']);
		session_regenerate_id();
		$_SESSION['authinfo']		= $authinfo;
		$_SESSION['server']		= $_POST['server'];
		$_SESSION['REMOTE_ADDR']	= $_SERVER['REMOTE_ADDR'];
	} catch (AccessDeniedException $e) {
		$login_error = $e->getMessage();
	}
} else if(isset($_SESSION['REMOTE_ADDR']) && $_SESSION['REMOTE_ADDR'] == $_SERVER['REMOTE_ADDR']) {
	$authinfo	= $_SESSION['authinfo'];
	$db	= ADONewConnection($cfg['Servers']['DB'][$_SESSION['server']]['DSN'])
		or die('Cannot connect to MySQL Server.');
	$db->SetFetchMode(ADODB_FETCH_ASSOC);
}

if(!isset($authinfo)) {
	// form
	include('./templates/'.$cfg['theme'].'/login.tpl');

	if(@is_readable($cfg['motd'])) {
		include('./templates/'.$cfg['theme'].'/motd.tpl');
	}

	include('./templates/'.$cfg['theme'].'/common-footer_nv.tpl');
	session_regenerate_id();
	hsys_ob_end($cfg['remove_whitespace']);
	exit();
}

?>