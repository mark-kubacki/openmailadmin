<?php
include('./inc/_prepend.php');
include('./inc/panel_filter.php');

$imap = new imapd_adm($IMAP);

// ------------------------------ Mailboxes -------------------------------------------------------
// PERFORM ACTION
if(isset($_POST['frm']) && $_POST['frm'] == 'user' && $oma->authenticated_user['a_admin_user'] >= 1) {
    if(!isset($_POST['action'])) {
	error(txt('112'));
    }
    else if(isset($_POST['action'])
		&& ($_POST['action'] == 'change' || $_POST['action'] == 'delete')
		&& !(isset($_POST['user']) && is_array($_POST['user']))) {
	error(txt('11'));
    }
    else {
	$oma->status_reset();
	switch($_POST['action']) {
	    case 'new':
		$oma->mailbox_create($_POST['mbox'], $_POST);
		break;
	    case 'delete':
		$oma->mailbox_delete($_POST['user']);
		break;
	    case 'change':
		$oma->mailbox_change($_POST['user'], $_POST['change'], $_POST);
		break;
	    case 'active':
		$oma->mailbox_toggle_active($_POST['user']);
		break;
	}

	if($oma->errors_occured()) {
	    error($oma->errors_get());
	}
	if($oma->info_occured()) {
	    info($oma->info_get());
	}
    }
}

// DATA
$mailboxes = $oma->get_mailboxes();

// DISPLAY
include('./templates/'.$cfg['theme'].'/mailboxes/list.tpl');

// ADMIN PANEL
if($oma->authenticated_user['a_admin_user'] >= 1) {
    // What paten may he select?
    $selectable_paten = $oma->get_selectable_paten($oma->current_user['mbox']);

    include('./templates/'.$cfg['theme'].'/mailboxes/admin.tpl');
}

include('./inc/_append.php');
?>