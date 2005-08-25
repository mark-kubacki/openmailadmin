<?php
include('inc/_prepend.php4');

$cyr = new cyradm;
$cyr->imap_login();
hsys_imap_detect_HS();

// ------------------------------ Information -----------------------------------------------------
$cuser['domain_set'] = getDomainSet($cuser['mbox'], $cuser['domains']);

$a_tmp = array();
if($cuser['a_super'] > 0)		$a_tmp[] = txt('68');
if($cuser['a_admin_domains'] > 0)	$a_tmp[] = txt('50');
if($cuser['a_admin_user'] > 0)	$a_tmp[] = txt('70');
if(count($a_tmp) > 0)	$rightstring = implode(', ', $a_tmp);
else			$rightstring = txt('85');
unset($a_tmp);

// DISPLAY
$information	= array();
$information[]	= array(txt('5'),	$cuser['mbox'].$CYRUS['VDOM']);
$information[]	= array(txt('6'),	$cuser['person']);
$information[]	= array(txt('7'),	$cuser['canonical']);
$information[]	= array(txt('86'),	$cuser['domains']);
$information[]	= array(txt('8'),	hsys_getMaxQuota($cuser['mbox']) == 'NOT-SET' ? '&infin;' : hsys_getUsedQuota($cuser['mbox']).' / '.hsys_getMaxQuota($cuser['mbox']).' [kiB]');
$information[]	= array(txt('77'),	$rightstring);
unset($rightstring);
include('templates/'.$cfg['theme'].'/information.tpl');
$cyr->imap_logout();

// ------------------------------ Password --------------------------------------------------------
if(isset($_POST['frm']) && $_POST['frm'] == 'pass' && $_POST['action'] == 'change')
{
    $pass_err = '';
    if($_POST['new_pass1'] != $_POST['new_pass2']) {
	$pass_err = txt('44');
    }
    if($cuser['mbox'] == $authinfo['mbox'] && !(passwd_check($_POST['old_pass'], $authinfo['pass_crypt']) || passwd_check($_POST['old_pass'], $authinfo['pass_md5']))) {
	$pass_err .= txt('45');
    }
    if(strlen($_POST['new_pass1']) < 8 || strlen($_POST['new_pass1']) > 16) {
	$pass_err .= txt('46');
    }
    if(!(preg_match('/[a-z]{1}/', $_POST['new_pass1']) && preg_match('/[A-Z]{1}/', $_POST['new_pass1']) && preg_match('/[0-9]{1}/', $_POST['new_pass1']))) {
	error(txt('47'));
    }
    if($pass_err == '') {
	$new_crypt	= crypt($_POST['new_pass1'], substr($_POST['new_pass1'],0,2));
	$new_md5	= md5($_POST['new_pass1']);
	mysql_query('UPDATE '.$cfg['tablenames']['user'].' SET pass_crypt=\''.$new_crypt.'\', pass_md5=\''.$new_md5.'\' WHERE mbox=\''.$cuser['mbox'].'\' LIMIT 1');
	if(mysql_affected_rows() > 0) {
	    info(txt('48'));
	    $_SESSION['pass_clear'] = $_POST['new_pass1'];
	}
	else {
	    error(mysql_error());
	}
    }
    else {
	error($pass_err);
    }
}

include('templates/'.$cfg['theme'].'/password.tpl');

include('inc/_append.php4');
?>