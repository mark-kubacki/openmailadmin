<?php
include('./inc/_prepend.php');
include('./inc/panel_filter.php');

$oma->current_user->domain_set = $oma->get_domain_set($oma->current_user->mbox, $oma->current_user->domains);
$oma->current_user->used_alias = $oma->user_get_used_alias($oma->current_user->mbox);

// ------------------------------ Addresses -------------------------------------------------------
// PERFORM ACTION
if(isset($_POST['frm']) && $_POST['frm'] == 'virtual') {
	if(!isset($_POST['action'])) {
		error(txt('112'));
	} else {
		if($_POST['action'] == 'new' || $_POST['action'] == 'dest') {
			// Set at least one valid destination.
			if(isset($_POST['dest_is_mbox']) && $_POST['dest_is_mbox'] == '1') {
				$destination = array($oma->current_user->mbox);
			} else {
				$destination = $oma->get_valid_destinations($_POST['dest']);
				if(count($destination) < 1) {
					$destination = array($oma->current_user->mbox);
					error(txt('10'));
				}
			}
		}
		// We need addresses as parameters for every action except the creation of new addresses.
		if($_POST['action'] != 'new' && (!isset($_POST['address']) || !is_array($_POST['address']))) {
			error(txt('11'));
		} else {
			switch($_POST['action']) {
				case 'new':
					$oma->address_create(trim($_POST['alias']), $_POST['domain'], $destination);
					break;
				case 'delete':
					$oma->address_delete($_POST['address']);
					break;
				case 'dest':
					$oma->address_change_destination($_POST['address'], $destination);
					break;
				case 'active':
					$oma->address_toggle_active($_POST['address']);
					break;
			}
			unset($destination);

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
$alias = $oma->get_addresses();

// DISPLAY
include('./templates/'.$cfg['theme'].'/addresses/list.tpl');

// ADMIN PANEL
include('./templates/'.$cfg['theme'].'/addresses/admin.tpl');

include('./inc/_append.php');
?>