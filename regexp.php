<?php
include('./inc/_prepend.php');
include('./inc/panel_filter.php');

// ------------------------------ Regexp ----------------------------------------------------------
// PERFORM ACTION
if(isset($_POST['frm']) && $_POST['frm'] == 'virtual_regexp') {
	if(!isset($_POST['action'])) {
		error(txt('112'));
	} else {
		if($oma->current_user->max_regexp != 0 && $oma->authenticated_user->max_regexp != 0) {
			if($_POST['action'] == 'new' || $_POST['action'] == 'dest') {
				// Set at least one valid destination.
				if(isset($_POST['dest_is_mbox']) && $_POST['dest_is_mbox'] == '1') {
					$destination = array($oma->current_user->mbox);
				} else {
					$destination = $oma->regexp->get_valid_destinations($_POST['dest']);
					if(count($destination) < 1) {
						$destination = array($oma->current_user->mbox);
						error(txt('10'));
					}
				}
			}
			// On every action except 'new' and 'probe' at least one expression
			// must be selected for manipulation and thus $_POST['expr'] be an array.
			if($_POST['action'] != 'new' && $_POST['action'] != 'probe'
			   && !(isset($_POST['expr']) && is_array($_POST['expr']))) {
				error(txt('11'));
			} else {
				switch($_POST['action']) {
					case 'new':
						$oma->regexp->create(trim($_POST['reg_exp']), $destination);
						break;
					case 'delete':
						$oma->regexp->delete($_POST['expr']);
						break;
					case 'dest':
						$oma->regexp->change_destination($_POST['expr'], $destination);
						break;
					case 'active':
						$oma->regexp->toggle_active($_POST['expr']);
						break;
				}

				if($ErrorHandler->errors_occured()) {
					error($ErrorHandler->errors_get());
				}
				if($ErrorHandler->info_occured()) {
					info($ErrorHandler->info_get());
				}
			}
			if(isset($destination)) unset($destination);
		} else {
			error(txt('16'));
		}
	}
}

// DATA
// We need to determine whether an string for matching shall be provided.
if(isset($_POST['frm']) && isset($_POST['action'])
   && $_POST['frm'] == 'virtual_regexp' && $_POST['action'] == 'probe') {
	$regexp = $oma->regexp->get_list($_POST['probe']);
} else {
	$regexp = $oma->regexp->get_list();
}

// DISPLAY
include('./templates/'.$cfg['theme'].'/regexp/list.tpl');

// ADMIN PANEL
if($oma->current_user->max_regexp != 0 && $oma->authenticated_user->max_regexp != 0) {
	// This is the action handler for 'probing'.
	if(isset($_POST['frm']) && isset($_POST['action'])
	   && $_POST['frm'] == 'virtual_regexp' && $_POST['action'] == 'probe'
	   && trim($_POST['reg_exp']) != '' && trim($_POST['probe']) != ''
	   && @preg_match($_POST['reg_exp'], $_POST['probe'])) {
		info(txt('36'));
	}

	include('./templates/'.$cfg['theme'].'/regexp/admin.tpl');
}

include('./inc/_append.php');
?>