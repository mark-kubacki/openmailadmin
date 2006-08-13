<?php
include('./inc/_prepend.php');
include('./inc/panel_filter.php');

// ------------------------------ Domains ---------------------------------------------------------
if($oma->authenticated_user->a_admin_domains > 0) {
	if(isset($_POST['frm']) && $_POST['frm'] == 'domains') {
		if(!isset($_POST['action'])) {
			error(txt('112'));
		} else if($_POST['action'] != 'new'
			   && !(isset($_POST['dom']) && count($_POST['dom']) > 0) ) {
			error(txt('11'));
		} else {
			switch($_POST['action']) {
				case 'new':
					$oma->domain->add($_POST['domain'], $_POST);
					break;
				case 'delete':
					$oma->domain->remove($_POST['dom']);
					break;
				case 'change':
					$oma->domain->change($_POST['dom'], $_POST['change'], $_POST);
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
}

// DATA
$domains = $oma->domain->get_list();

// DISPLAY
include('./templates/'.$cfg['theme'].'/domains/list.tpl');

if($oma->authenticated_user->a_admin_domains > 0) {
	$selectable_paten = $oma->mailbox->get_selectable_paten($oma->current_user);
	// ADMIN PANEL
	include('./templates/'.$cfg['theme'].'/domains/admin.tpl');
}

include('./inc/_append.php');
?>