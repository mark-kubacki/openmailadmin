<?php
include('./inc/_prepend.php');
include('./inc/panel_filter.php');

// ------------------------------ Mailboxes -------------------------------------------------------
// PERFORM ACTION
if(isset($_POST['frm']) && $_POST['frm'] == 'user' && $oma->authenticated_user->a_admin_user >= 1) {
	if(!isset($_POST['action'])) {
		error(txt('112'));
	} else if(isset($_POST['action'])
			&& ($_POST['action'] == 'change' || $_POST['action'] == 'delete')
			&& !(isset($_POST['user']) && is_array($_POST['user']))) {
		error(txt('11'));
	} else {
		switch($_POST['action']) {
			case 'new':
				$oma->mailbox->create($_POST['mbox'], $_POST);
				break;
			case 'delete':
				$oma->mailbox->delete($_POST['user']);
				break;
			case 'change':
				$oma->mailbox->change($_POST['user'], $_POST['change'], $_POST);
				break;
			case 'active':
				$oma->mailbox->toggle_active($_POST['user']);
				break;
		}

		if($ErrorHandler->errors_occured()) {
			error($ErrorHandler->errors_get());
		}
		if($ErrorHandler->info_occured()) {
			info($ErrorHandler->info_get());
		}
	}
}

// DATA
$mailboxes = $oma->mailbox->get_list();

// DISPLAY
include('./templates/'.$cfg['theme'].'/mailboxes/list.tpl');

// ADMIN PANEL
if($oma->authenticated_user->a_admin_user >= 1) {
	// What paten may he select?
	$selectable_paten = $oma->mailbox->get_selectable_paten($oma->current_user);

	include('./templates/'.$cfg['theme'].'/mailboxes/admin.tpl');
}

include('./inc/_append.php');
?>