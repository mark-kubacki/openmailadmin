<?php
//	Mini-Auth
ini_set('session.use_only_cookies', 1);
session_start();

// authentification
if(isset($_GET['login']) && $_GET['login'] == 'change') {
    session_destroy();
    $_SESSION = array();
}
else if(isset($_POST['frm']) && $_POST['frm'] == 'login' && trim($_POST['mboxname']) != '') {
    if(!(isset($_POST['server']) && is_numeric($_POST['server'])))
	$_POST['server'] = 0;
    mysql_connect($cfg['Servers']['DB'][$_POST['server']]['HOST'], $cfg['Servers']['DB'][$_POST['server']]['USER'], $cfg['Servers']['DB'][$_POST['server']]['PASS']) or die('Cannot connect to MySQL Server.');
    mysql_select_db($cfg['Servers']['DB'][$_POST['server']]['DB']) or die('Cannot select Database');
    if(isset($cfg['Servers']['IMAP'][$_POST['server']]))
	$CYRUS = $cfg['Servers']['IMAP'][$_POST['server']];		// $CYRUS is needed by our IMAP-library
    else
	$CYRUS = $cfg['Servers']['CYRUS'][$_POST['server']];

    $result = mysql_query('SELECT * FROM '.$cfg['Servers']['DB'][$_POST['server']]['PREFIX'].'user WHERE mbox="'.mysql_real_escape_string($_POST['mboxname']).'" AND active=1 LIMIT 1');
    if(mysql_num_rows($result) > 0) {
	$authinfo = mysql_fetch_assoc($result);
	mysql_free_result($result);
	if(($authinfo['pass_md5'] == '' && passwd_check($_POST['password'], $authinfo['pass_crypt']))
		|| passwd_check($_POST['password'], $authinfo['pass_md5'])) {
	    $authinfo['pass_clear'] = obfuscator_encrypt($_POST['password']);
	    unset($_POST['password']);
	    mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['Servers']['DB'][$_POST['server']]['PREFIX'].'user SET last_login=FROM_UNIXTIME('.time().') WHERE mbox="'.$authinfo['mbox'].'" LIMIT 1');
	    session_regenerate_id();
	    $_SESSION			= $authinfo;
	    $_SESSION['server']		= $_POST['server'];
	    $_SESSION['REMOTE_ADDR']	= $_SERVER['REMOTE_ADDR'];
	}
	else {
	    unset($authinfo);
	    $login_error = txt('0');
	    mysql_close();
	}
    }
    else {
	$login_error = txt('0');
	mysql_close();
    }
}
else if(isset($_SESSION['REMOTE_ADDR']) && $_SESSION['REMOTE_ADDR'] == $_SERVER['REMOTE_ADDR']) {
    $authinfo	= $_SESSION;
    mysql_connect($cfg['Servers']['DB'][$_SESSION['server']]['HOST'], $cfg['Servers']['DB'][$_SESSION['server']]['USER'], $cfg['Servers']['DB'][$_SESSION['server']]['PASS']) or die('Cannot connect to MySQL Server.');
    mysql_select_db($cfg['Servers']['DB'][$_SESSION['server']]['DB']) or die('Cannot select Database');
	$CYRUS = $cfg['Servers']['IMAP'][$_SESSION['server']];		// $CYRUS is needed by our IMAP-library
}

if(!isset($authinfo)) {
    // form
    include('templates/'.$cfg['theme'].'/login.tpl');

    if(@is_readable($cfg['motd'])) {
	include('templates/'.$cfg['theme'].'/motd.tpl');
    }

    include('templates/'.$cfg['theme'].'/common-footer_nv.tpl');
    session_regenerate_id();
    hsys_ob_end();
    exit();
}

?>