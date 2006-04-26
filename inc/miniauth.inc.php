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

	$authinfo = $db->GetRow('SELECT * FROM '.$cfg['Servers']['DB'][$_POST['server']]['PREFIX'].'user WHERE mbox='.$db->qstr($_POST['mboxname']).' AND active=1');
	if(!$authinfo === false) {
		if(passwd_check($_POST['password'], $authinfo['pass_md5'])) {
			$authinfo['pass_clear'] = obfuscator_encrypt($_POST['password']);
			unset($_POST['password']);
			$db->Execute('UPDATE LOW_PRIORITY '.$cfg['Servers']['DB'][$_POST['server']]['PREFIX'].'user SET last_login='.time().' WHERE mbox='.$db->qstr($authinfo['mbox']).' LIMIT 1');
			session_regenerate_id();
			$_SESSION			= $authinfo;
			$_SESSION['server']		= $_POST['server'];
			$_SESSION['REMOTE_ADDR']	= $_SERVER['REMOTE_ADDR'];
		} else {
			unset($authinfo);
			$login_error = txt('0');
		}
	} else {
		unset($authinfo);
		$login_error = txt('0');
	}
} else if(isset($_SESSION['REMOTE_ADDR']) && $_SESSION['REMOTE_ADDR'] == $_SERVER['REMOTE_ADDR']) {
	$authinfo	= $_SESSION;
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