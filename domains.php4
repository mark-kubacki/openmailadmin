<?php
include('inc/_prepend.php4');
include('inc/panel_filter.php4');

// ------------------------------ Domains ---------------------------------------------------------
if($oma->authenticated_user['a_admin_domains'] > 0) {
    if(isset($_POST['frm']) && $_POST['frm'] == 'domains') {
	if(!isset($_POST['action'])) {
	    error(txt('112'));
	}
	else
	if(isset($_POST['dom']) && !is_array($_POST['dom']) && $_POST['action'] != 'new') {
	    error(txt('11'));
	}
	else {
	    $oma->status_reset();
	    switch($_POST['action']) {
		case 'new':
		    $oma->domain_add($_POST['domain'], $_POST);
		    break;
		case 'delete':
		    $oma->domain_remove($_POST['dom']);
		    break;
		case 'change':
		    $oma->domain_change($_POST['dom'], $_POST['change'], $_POST);
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
}

// DATA
$domains = $oma->get_domains();

// DISPLAY
include('templates/'.$cfg['theme'].'/domains/list.tpl');

if($oma->authenticated_user['a_admin_domains'] > 0) {
    // ADMIN PANEL
    include('templates/'.$cfg['theme'].'/domains/admin.tpl');
}

include('inc/_append.php4');
?>