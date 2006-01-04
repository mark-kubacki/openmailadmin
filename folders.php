<?php
include('./inc/_prepend.php');

// ------------------------------ Folder & ACL ----------------------------------------------------
if($oma->current_user['mbox'] != $oma->authenticated_user['mbox']) {
	error(txt('104'));
	include('./inc/_append.php');
	exit();
}

// we shall log in as the current user
$IMAP		= $cfg['Servers']['IMAP'][$_SESSION['server']];
$IMAP['ADMIN']	= $oma->authenticated_user['mbox'].$IMAP['VDOM'];
$IMAP['PASS']	= obfuscator_decrypt($oma->authenticated_user['pass_clear']);
if($cfg['Servers']['IMAP'][$_SESSION['server']]['TYPE'] == 'cyrus') {
	$imap	= new Cyrus_IMAP($IMAP);
}

// ACTION
if(isset($_POST['frm']) && $_POST['frm'] == 'ACL') {
	if(!isset($_POST['action'])) {
		error(txt('112'));
	} else
	switch($_POST['action']) {
		case 'new':
			if(isset($_GET['folder'])) {
				$_GET['folder']	= trim($_GET['folder']);
				$_POST['subname']	= trim($_POST['subname']);
				if(preg_match('/[\w\s\d\+\-\_\.\:\~\=]{'.strlen($_POST['subname']).'}/', $_POST['subname'])) {
					$to_be_created = addslashes($_GET['folder'].$imap->gethierarchyseparator().$_POST['subname']);
					$imap->createmb($to_be_created);
				} else {
					error(txt('109'));
				}
			}
			break;
		case 'delete':
			if(isset($_GET['folder'])) {
				$imap->deletemb(addslashes(trim($_GET['folder'])));
				$_GET['folder'] = 'INBOX';
			}
			break;
		case 'rights':
			if(!isset($_POST['moduser']) || trim($_POST['moduser']) == '') {
				error(txt('111'));
			} else if(isset($_GET['folder'])) {
				if($_POST['modaclsel'] == 'above') {
					if(isset($_POST['modacl']) && count($_POST['modacl']) > 0)
						$imap->setacl(addslashes(trim($_GET['folder'])), $_POST['moduser'], implode('', $_POST['modacl']));
					if($imap->error_msg != '')
						error($imap->error_msg);
				} else {
					$imap->setacl(addslashes(trim($_GET['folder'])), $_POST['moduser'], addslashes($_POST['modaclsel']));
				}
			}
			break;
	}
}

// DATA
$raw_folder_list = $imap->getmailboxes();

// merge all steps
$mbox_arr = array();
for($i = 0; $i < count($raw_folder_list); $i++) {
	$mailbox_list[] = $raw_folder_list[$i]['name'];
	$mbox_arr = array_merge_recursive($mbox_arr, array_stepper($raw_folder_list[$i]['delimiter'], $raw_folder_list[$i]['name']));
}

// DISPLAY
include('./templates/'.$cfg['theme'].'/folders/list.tpl');

// ADMIN PANEL (not hidden by default)
if(isset($_GET['folder'])&& in_array($_GET['folder'], $mailbox_list)) {
	$ACLs = $imap->getacl($_GET['folder']);
	ksort($ACLs);
	reset($ACLs);
	$has_acl_a = isset($ACLs[$oma->authenticated_user['mbox'].$IMAP['VDOM']]) && stristr($ACLs[$oma->authenticated_user['mbox'].$IMAP['VDOM']], 'a')
			|| isset($ACLs['anyone']) && stristr($ACLs['anyone'], 'a');

	include('./templates/'.$cfg['theme'].'/folders/admin.tpl');
}

include('./inc/_append.php');
?>