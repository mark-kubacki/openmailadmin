<?php
include('inc/_prepend.php4');

$cyr = new cyradm;
$cyr->imap_login();
hsys_imap_detect_HS();

// ------------------------------ Information -----------------------------------------------------
$oma->current_user['domain_set'] = $oma->get_domain_set($oma->current_user['mbox'], $oma->current_user['domains']);

$a_tmp = array();
if($oma->current_user['a_super'] > 0)		$a_tmp[] = txt('68');
if($oma->current_user['a_admin_domains'] > 0)	$a_tmp[] = txt('50');
if($oma->current_user['a_admin_user'] > 0)	$a_tmp[] = txt('70');
if(count($a_tmp) > 0)	$rightstring = implode(', ', $a_tmp);
else			$rightstring = txt('85');
unset($a_tmp);

// DISPLAY
$information	= array();
$information[]	= array(txt('5'),	$oma->current_user['mbox'].$cfg['Servers']['IMAP'][$_SESSION['server']]['VDOM']);
$information[]	= array(txt('6'),	$oma->current_user['person']);
$information[]	= array(txt('7'),	$oma->current_user['canonical']);
$information[]	= array(txt('86'),	$oma->current_user['domains']);
$information[]	= array(txt('8'),	hsys_getMaxQuota($oma->current_user['mbox']) == 'NOT-SET' ? '&infin;' : hsys_getUsedQuota($oma->current_user['mbox']).' / '.hsys_getMaxQuota($oma->current_user['mbox']).' [kiB]');
$information[]	= array(txt('77'),	$rightstring);
unset($rightstring);
include('templates/'.$cfg['theme'].'/information.tpl');
$cyr->imap_logout();

// ------------------------------ Password --------------------------------------------------------
if(isset($_POST['frm']) && $_POST['frm'] == 'pass' && $_POST['action'] == 'change') {
    $oma->status_reset();
    if($oma->current_user['mbox'] == $oma->authenticated_user['mbox']) {
	if($oma->user_change_password($_POST['new_pass1'], $_POST['new_pass2'], $_POST['old_pass'])) {
	    // we have to reset the current user's cleartext password
            // $_SESSION will later be read as $oma->authenticated_user
	    $_SESSION['pass_clear'] = $_POST['new_pass1'];
	}
    }
    else {
	$oma->user_change_password($_POST['new_pass1'], $_POST['new_pass2']);
    }

    if($oma->errors_occured()) {
	error($oma->errors_get());
    }
    if($oma->info_occured()) {
	info($oma->info_get());
    }
}

include('templates/'.$cfg['theme'].'/password.tpl');

include('inc/_append.php4');
?>