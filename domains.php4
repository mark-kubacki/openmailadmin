<?php
include('inc/_prepend.php4');
include('inc/panel_filter.php4');

// ------------------------------ Domains ---------------------------------------------------------
if($authinfo['a_admin_domains'] > 0) {
    if(isset($_POST['frm']) && $_POST['frm'] == 'domains') {
	if(!isset($_POST['action'])) {
	    error(txt('112'));
	}
	else
	if(isset($_POST['dom']) && !is_array($_POST['dom']) && $_POST['action'] != 'new') {
	    error(txt('11'));
	}
	else
	switch($_POST['action']) {
	    case 'new':
		if(trim($_POST['a_admin']) == '')
		    $_POST['a_admin'] = $cuser['mbox'];
		if(trim($_POST['owner']) == '')
		    $_POST['owner'] = $cuser['mbox'];
		if(!stristr($_POST['categories'], 'all'))
		    $_POST['categories'] = 'all, '.$_POST['categories'];
		if(!stristr($_POST['a_admin'].$_POST['owner'], $cuser['mbox']))
		    $_POST['a_admin'] .= ' '.$cuser['mbox'];
		if(preg_match('/[a-z0-9\-\_\.]{2,}\.[a-z]{2,}/i', $_POST['domain'])) {
		    mysql_query('INSERT INTO '.$cfg['tablenames']['domains'].' (domain, categories, owner, a_admin) VALUES (\''.mysql_escape_string($_POST['domain']).'\', \''.mysql_escape_string($_POST['categories']).'\', \''.mysql_escape_string($_POST['owner']).'\', \''.mysql_escape_string($_POST['a_admin']).'\')');
		    if(mysql_affected_rows() < 1)
			error(mysql_error());
		}
		else
		    error(txt('51'));
		break;
	    case 'delete':
		// we need the old domain name later...
		if(isset($_POST['dom']) && count($_POST['dom']) > 0) {
		    if($cfg['admins_delete_domains'])
			$result = mysql_query('SELECT ID, domain FROM '.$cfg['tablenames']['domains'].' WHERE (owner=\''.$authinfo['mbox'].'\' or a_admin LIKE \'%'.$authinfo['mbox'].'%\') AND FIND_IN_SET(ID, "'.mysql_escape_string(implode(',', $_POST['dom'])).'") LIMIT '.count($_POST['dom']));
		    else
			$result = mysql_query('SELECT ID, domain FROM '.$cfg['tablenames']['domains'].' WHERE owner=\''.$authinfo['mbox'].'\' AND FIND_IN_SET(ID, "'.mysql_escape_string(implode(',', $_POST['dom'])).'") LIMIT '.count($_POST['dom']));
		    if(mysql_num_rows($result) > 0) {
			while($domain = mysql_fetch_assoc($result)) {
			    $del_ID[] = $domain['ID'];
			    $del_nm[] = $domain['domain'];
			}
			mysql_free_result($result); unset($domain);
			mysql_query('DELETE FROM '.$cfg['tablenames']['domains'].' WHERE FIND_IN_SET(ID, "'.implode(',',$del_ID).'") LIMIT '.count($del_ID));
			if(mysql_affected_rows() < 1)
			    error(mysql_error());
			else {
			    info(txt('52').'<br />'.implode(', ',$del_nm));
			    // We better deactivate all aliases containing that domain, so users can see the domain was deleted.
			    mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual'].' SET active = 0, neu = 1 WHERE FIND_IN_SET(SUBSTRING(address, LOCATE(\'@\', address)+1), \''.implode(',', $del_nm).'\')');
			    // We can't do such on REGEXP addresses: They may catch more than the given domains.
			}
		    }
		    else {
			error(txt('16'));
		    }
		}
		break;
	    case 'change':
		if($cfg['admins_delete_domains'] && isset($_POST['c_owner']) && $_POST['c_owner'] == '1')
		    $toc[] = 'owner=\''.mysql_escape_string($_POST['owner']).'\'';
		if(isset($_POST['c_admin']) && $_POST['c_admin'] == '1')
		    $toc[] = 'a_admin=\''.mysql_escape_string($_POST['a_admin']).'\'';
		if(isset($_POST['c_cat']) && $_POST['c_cat'] == '1')
		    $toc[] = 'categories=\''.mysql_escape_string($_POST['categories']).'\'';
		if(isset($toc) && is_array($toc)) {
		    mysql_query('UPDATE '.$cfg['tablenames']['domains'].' SET '.implode(',', $toc).' WHERE (owner=\''.$authinfo['mbox'].'\' or a_admin LIKE \'%'.$authinfo['mbox'].'%\') AND FIND_IN_SET(ID, "'.mysql_escape_string(implode(',', $_POST['dom'])).'") LIMIT '.count($_POST['dom']));
		    if(mysql_affected_rows() < 1) {
			if(mysql_error() != '')
			    error(mysql_error());
			else
			    error(txt('16'));
		    }
		}
		// changing ownership if $cfg['admins_delete_domains'] == false
                if(!$cfg['admins_delete_domains'] && isset($_POST['c_owner']) && $_POST['c_owner'] == '1') {
		    mysql_query('UPDATE '.$cfg['tablenames']['domains'].' SET owner=\''.mysql_escape_string($_POST['owner']).'\' WHERE owner=\''.$authinfo['mbox'].'\' AND FIND_IN_SET(ID, "'.mysql_escape_string(implode(',', $_POST['dom'])).'") LIMIT '.count($_POST['dom']));
		}
		// Any domain to be renamed?
		if(! (isset($_POST['c_name']) && $_POST['c_name'] == '1')) {
		    break;
		}
		// Was only one domain selected?
		if(count($_POST['dom']) == 1) {
		    if(preg_match('/[a-z0-9\-\_\.]{2,}\.[a-z]{2,}/i', $_POST['domain'])) {
			$result = mysql_query('SELECT ID, domain AS name FROM '.$cfg['tablenames']['domains'].' WHERE ID = "'.mysql_escape_string($_POST['dom'][0]).'" AND (owner=\''.$authinfo['mbox'].'\' or a_admin LIKE \'%'.$authinfo['mbox'].'%\')');
			if(mysql_num_rows($result) == 1) {
			    $domain = mysql_fetch_assoc($result);
			    mysql_free_result($result);
			    // First, update the name. (Corresponding field is marked as unique, therefore we will not receive doublettes.)
			    mysql_query('UPDATE '.$cfg['tablenames']['domains'].' SET domain = \''.$_POST['domain'].'\' WHERE ID = '.$domain['ID'].' LIMIT 1');
			    if(mysql_affected_rows() == 1) {
				// address
				mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual'].' SET neu = 1, address = REPLACE(address, "@'.$domain['name'].'", "@'.$_POST['domain'].'") WHERE address LIKE \'%@'.$domain['name'].'\'');
				// dest
				mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual'].' SET neu = 1, dest = REPLACE(dest, "@'.$domain['name'].'", "@'.$_POST['domain'].'") WHERE dest LIKE \'%@'.$domain['name'].'%\'');
				mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual_regexp'].' SET neu = 1, dest = REPLACE(dest, "@'.$domain['name'].'", "@'.$_POST['domain'].'") WHERE dest LIKE \'%@'.$domain['name'].'%\'');
				// canonical
				mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['user'].' SET canonical = REPLACE(canonical, "@'.$domain['name'].'", "@'.$_POST['domain'].'") WHERE canonical LIKE \'%@'.$domain['name'].'\'');
			    }
			    else
				error(mysql_error());
			}
			else
			    error(txt('91'));
		    }
		    else
			error(txt('51'));
		}
		else
		    error(txt('53'));
		break;
	}
    }
}

// DATA
if($authinfo['a_super'] > 0)
    $result = mysql_query('SELECT SQL_CALC_FOUND_ROWS * FROM '.$cfg['tablenames']['domains'].' WHERE 1=1 '.$_SESSION['filter']['str']['domain'].' ORDER BY owner, length(a_admin), domain'.$_SESSION['limit']['str']['domain']);
else
    $result = mysql_query('SELECT SQL_CALC_FOUND_ROWS * FROM '.$cfg['tablenames']['domains'].' WHERE (owner=\''.$cuser['mbox'].'\' or a_admin LIKE \'%'.$cuser['mbox'].'%\')'.$_SESSION['filter']['str']['domain'].' ORDER BY owner, length(a_admin), domain'.$_SESSION['limit']['str']['domain']);
$domains = array();
if(mysql_num_rows($result) > 0) {
    $editable_domains = 0;
    while($row = mysql_fetch_assoc($result)) {
	if($row['owner'] == $authinfo['mbox'] || find_in_set($authinfo['mbox'], $row['a_admin'])) {
	    $row['selectable']	= true;
	    ++$editable_domains;
	}
	else {
	    $row['selectable']	= false;
	}
	$domains[] = $row;
    }
    mysql_free_result($result);
    $result = mysql_query('SELECT FOUND_ROWS()');
    $cuser['n_domains'] = mysql_result($result, 0, 0);
    mysql_free_result($result);

}
// DISPLAY
include('templates/'.$cfg['theme'].'/domains/list.tpl');

if($authinfo['a_admin_domains'] > 0) {
    // ADMIN PANEL
    include('templates/'.$cfg['theme'].'/domains/admin.tpl');
}

include('inc/_append.php4');
?>