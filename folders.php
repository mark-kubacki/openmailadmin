<?php
include('inc/_prepend.php');

// ------------------------------ Folder & ACL ----------------------------------------------------
if($oma->current_user['mbox'] == $oma->authenticated_user['mbox']) {
    // we shall log in as the current user
    $CYRUS['ADMIN'] = $oma->authenticated_user['mbox'].$CYRUS['VDOM'];
    $CYRUS['PASS'] = obfuscator_decrypt($oma->authenticated_user['pass_clear']);
    $cyr = new cyradm;
    $cyr->imap_login();

    // ACTION
    if(isset($_POST['frm']) && $_POST['frm'] == 'ACL') {
	if(!isset($_POST['action'])) {
	    error(txt('112'));
	}
	else
	switch($_POST['action']) {
	    case 'new':
		if(isset($_GET['folder'])) {
		    $_GET['folder']	= trim($_GET['folder']);
		    $_POST['subname']	= trim($_POST['subname']);
		    if(preg_match('/[\w\s\d\+\-\_\.\:\~\=]{'.strlen($_POST['subname']).'}/', $_POST['subname'])) {
			hsys_imap_detect_HS();
			$to_be_created = addslashes($_GET['folder'].$CYRUS['SEPA'].$_POST['subname']);
			$cyr->createmb($to_be_created);
		    }
		    else {
			error(txt('109'));
		    }
		}
		break;
	    case 'delete':
		if(isset($_GET['folder'])) {
		    $cyr->deletemb(addslashes(trim($_GET['folder'])));
		    $_GET['folder'] = 'INBOX';
		}
		break;
	    case 'rights':
		if(!isset($_POST['moduser']) || trim($_POST['moduser']) == '') {
		    error(txt('111'));
		}
		else if(isset($_GET['folder'])) {
		    if($_POST['modaclsel'] == 'above') {
			if(isset($_POST['modacl']) && count($_POST['modacl']) > 0)
			    $cyr->setacl(addslashes(trim($_GET['folder'])), $_POST['moduser'], implode('', $_POST['modacl']));
		    }
		    else {
			$cyr->setacl(addslashes(trim($_GET['folder'])), $_POST['moduser'], addslashes($_POST['modaclsel']));
		    }
		}
		break;
	}
    }

    // DATA
    $raw_folder_list = hsys_getFolderInfo(hsys_imap_getfolders());

    // merge all steps
    $mbox_arr = array();
    for($i = 0; $i < count($raw_folder_list); $i++) {
	$mailbox_list[] = $raw_folder_list[$i]['mailbox'];
	$mbox_arr = array_merge_recursive($mbox_arr, array_stepper($raw_folder_list[$i]['separator'], $raw_folder_list[$i]['mailbox']));
    }

    // DISPLAY
    include('templates/'.$cfg['theme'].'/folders/list.tpl');

    // ADMIN PANEL (not hidden by default)
    if(isset($_GET['folder'])&& in_array($_GET['folder'], $mailbox_list)) {
	$ACLs = $cyr->getacl($_GET['folder']);
	ksort($ACLs);
	reset($ACLs);
	$has_acl_a = isset($ACLs[$oma->authenticated_user['mbox'].$CYRUS['VDOM']]) && stristr($ACLs[$oma->authenticated_user['mbox'].$CYRUS['VDOM']], 'a')
		    || isset($ACLs['anyone']) && stristr($ACLs['anyone'], 'a');

	include('templates/'.$cfg['theme'].'/folders/admin.tpl');
    }

    $cyr->imap_logout();
}
else {
    error(txt('104'));
}

include('inc/_append.php');
?>