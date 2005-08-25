<?php
include('inc/_prepend.php4');
include('inc/panel_filter.php4');

$cuser['domain_set'] = getDomainSet($cuser['mbox'], $cuser['domains']);
$cuser['used_alias'] = hsys_getUsedAlias($cuser['mbox']);

// ------------------------------ Addresses -------------------------------------------------------
// PERFORM ACTION
if(isset($_POST['frm']) && $_POST['frm'] == 'virtual') {
    if(!isset($_POST['action'])) {
	error(txt('112'));
    }
    else
    {
	if($_POST['action'] == 'new' || $_POST['action'] == 'dest') {
	    // Set at least one valid destination.
	    if(isset($_POST['dest_is_mbox']) && $_POST['dest_is_mbox'] == '1')
		$destination = array($cuser['mbox']);
	    else {
		$destination = $oma->get_valid_destinations($_POST['dest']);
		if(count($destination) < 1) {
		    $destination = array($cuser['mbox']);
		    error(txt('10'));
		}
		// Modify the user's 'dest'-field as well.
		$_POST['dest'] = implode("\n", $destination);
	    }
	}
	// We need addresses as parameters for every action except the creation of new addresses.
	if($_POST['action'] != 'new' && (!isset($_POST['address']) || !is_array($_POST['address']))) {
	    error(txt('11'));
	}
	else
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

	if($oma->errors_occured()) {
	    error($oma->errors_get());
	}
	if($oma->info_occured()) {
	    info($oma->info_get());
	}
    }
}

// DATA
$alias = $oma->get_addresses();

// DISPLAY
include('templates/'.$cfg['theme'].'/addresses/list.tpl');

// ADMIN PANEL
include('templates/'.$cfg['theme'].'/addresses/admin.tpl');

include('inc/_append.php4');
?>