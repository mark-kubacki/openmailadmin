<?php
include('inc/_prepend.php4');
include('inc/panel_filter.php4');

$cyr = new cyradm;
$cyr->imap_login();
hsys_imap_detect_HS();

// ------------------------------ Mailboxes -------------------------------------------------------
// PERFORM ACTION
if(isset($_POST['frm']) && $_POST['frm'] == 'user' && $authinfo['a_admin_user'] >= 1)
if(!isset($_POST['action'])) {
    error(txt('112'));
}
else  {
    $req_error = '';
    // beautify
    if(isset($_POST['quota']) && $_POST['quota'] != 'NOT-SET') {
	$_POST['quota'] = abs(intval($_POST['quota']));
    }
    if(isset($_POST['max_alias']))
	$_POST['max_alias'] = abs(intval($_POST['max_alias']));
    else
	$_POST['max_alias'] = 0;
    if(isset($_POST['max_regexp']))
	$_POST['max_regexp'] = abs(intval($_POST['max_regexp']));
    else
	$_POST['max_regexp'] = 0;
    // requirements
    if(($_POST['action'] == 'change' && (isset($_POST['c_quota']) && $_POST['c_quota'] == 1) || $_POST['action'] == 'new') && $_POST['quota'] == 'NOT-SET' && ($authinfo['a_super'] == 0 || hsys_getMaxQuota($cuser['mbox']) != 'NOT-SET')) {
	$req_error .= txt('63');
    }
    if($_POST['action'] == 'change' || $_POST['action'] == 'delete' || $_POST['action'] == 'active') {
	if(count($_POST['user']) < 1) {
	    $req_error .= txt('11');
	}
    }
    if($_POST['action'] == 'change' && (isset($_POST['c_mbox']) && isset($_POST['user'])) && $_POST['c_mbox'] == 1 && count($_POST['user']) != 1) {
	$req_error .= txt('91');
    }
    if($req_error == '' && ($_POST['action'] == 'new' || $_POST['action'] == 'change')) {
	if($_POST['action'] == 'new' && !(isset($_POST['mbox']) && isset($_POST['person']) && isset($_POST['canonical']) && isset($_POST['reg_exp']) && isset($_POST['domains']))) {
	    $req_error .= txt('61');
	}
	if(($_POST['action'] == 'new' || (isset($_POST['c_mbox']) && $_POST['c_mbox'] == 1)) && (strlen($_POST['mbox']) < 4 || strlen($_POST['mbox']) > 16 || !preg_match('/[a-zA-Z0-9]{'.strlen($_POST['mbox']).'}/', $_POST['mbox']))) {
	    $req_error .= txt('62');
	}
	if(($_POST['action'] == 'new' || (isset($_POST['c_canon']) && $_POST['c_canon'] == 1)) && !preg_match('/[A-Za-z0-9][A-Za-z0-9\.\-\_\+]{1,}@[A-Za-z0-9\.\-\_]{2,}\.[A-Za-z]{2,}/', $_POST['canonical'])) {
	    $req_error .= txt('64');
	}
	if($_POST['action'] == 'change' && isset($_POST['c_mbox']) && $_POST['c_mbox'] == 1 && $_POST['mbox'] == $_POST['user']['0']) {
	    $req_error .= txt('93');
	}
	if($req_error == '' && $_POST['action'] == 'change' && isset($_POST['c_mbox']) && $_POST['c_mbox'] == 1) {
	    // Is the desired name already in use?
	    $result = mysql_query('SELECT COUNT(mbox) AS number FROM '.$cfg['tablenames']['user'].' WHERE mbox = \''.$_POST['mbox'].'\'');
	    $tmp = mysql_fetch_assoc($result);
	    mysql_free_result($result);
	    if($tmp['number'] > 0) {
		$req_error .= txt('92');
	    }
	    unset($tmp); unset($result);
	}
	// check quota and address contingents
	if((isset($_POST['c_domains']) && $_POST['action'] == 'change') || $_POST['action'] == 'new') {
	    if(!isset($cuser['domain_set']))
		$cuser['domain_set'] = getDomainSet($cuser['mbox'], $cuser['domains']);
	    // new domain-key must not lead to more domains than the user already has to choose from
	    // what domains the new user will have to choose from? A
	    $dom_a = getDomainSet(mysql_escape_string($_POST['action'] == 'change' ? $_POST['user'][0] : $_POST['mbox']), mysql_escape_string($_POST['domains']));
	    // what domains the creator may choose from? B
	    // okay, if A plus B is A (thus, no additional domains are added)
	    $dom_ab = array_unique(array_merge($dom_a, $cuser['domain_set']));
	    if(count($dom_a) == 0) {
		$req_error .= txt('80');
	    }
	    else if(count($cuser['domain_set']) != count($dom_ab)) {
		$req_error .= txt('81');
	    }
	}
	if($authinfo['a_super'] == 0 && hsys_getMaxQuota($cuser['mbox']) != 'NOT-SET' &&
		$_POST['action'] == 'new' && $_POST['quota'] > (hsys_getMaxQuota($cuser['mbox']) - hsys_getUsedQuota($cuser['mbox']))) {
	    $req_error .= txt('65');	// does not apply if action==change! (see below)
	}
	if($authinfo['a_super'] == 0
	    && ($_POST['action'] == 'new' || $_POST['c_alias'] + $_POST['c_regexp'] > 0)
	    && ($_POST['max_alias'] > ($cuser['max_alias'] - hsys_getUsedAlias($cuser['mbox']))
		|| $_POST['max_regexp'] > ($cuser['max_regexp'] - hsys_getUsedRegexp($cuser['mbox'])))) {
	    $req_error .= txt('66');
	}
	if($req_error == '') {
	    // are the users descendants of the authentificated user?
	    if($authinfo['a_super'] == 0 && isset($_POST['user']) && is_array($_POST['user'])) {
		foreach($_POST['user'] as $key=>$user) {
		    if($user != '') {
			if(!IsDescendant($user, $authinfo['mbox'])) {
			    $req_error = txt('16');
			    break;
			}
		    }
		}
		reset($_POST['user']);
	    }
	    // Quota
	    if($req_error == '' && $_POST['action'] == 'change' && $authinfo['a_super'] == 0 && $_POST['c_quota'] == 1 && hsys_getMaxQuota($cuser['mbox']) != 'NOT-SET') {
		foreach($_POST['user'] as $key=>$user) {
		    if($user != '') {
			if(hsys_getMaxQuota($user) != 'NOT-SET')
			    $add_quota += $_POST['quota'] - hsys_getMaxQuota($user);
		    }
		}
		reset($_POST['user']);
		if(hsys_getMaxQuota($cuser['mbox']) - hsys_getUsedQuota($cuser['mbox']) < $add_quota) {
		    $req_error = txt('65');
		    $req_error .= sprintf(txt('67'), $add_quota, (hsys_getMaxQuota($cuser['mbox']) - hsys_getUsedQuota($cuser['mbox'])));
		}
		unset($add_quota);
	    }
	}
    }

    if($req_error != '') {
	error($req_error);
    }
    else {
	switch($_POST['action']) {
	    case 'new':
		if($cfg['create_canonical']) {
		    // first create the default-from (canonical) (must not already exist!)
		    $sql = 'INSERT INTO '.$cfg['tablenames']['virtual'].' (address, dest, owner) ';
		    $sql .= 'VALUES (\''.mysql_escape_string($_POST['canonical']).'\', \''.mysql_escape_string($_POST['mbox']).'\', \''.mysql_escape_string($_POST['mbox']).'\')';
		    mysql_query($sql);
		    if(mysql_affected_rows() < 1) {
			error(mysql_error());
			break;
		    }
		}
		// on success write the new user to database
		$sql_a1 = ''; $sql_a2 = '';
		if(isset($_POST['a_a_dom']) && is_numeric($_POST['a_a_dom']) && $authinfo['a_admin_domains'] >= 2) {
		    $sql_a1 .= ', a_admin_domains';
		    $sql_a2 .= ', '.(max(0, min(intval($_POST['a_a_dom']), $authinfo['a_admin_domains'])));
		}
		if(isset($_POST['a_a_usr']) && is_numeric($_POST['a_a_usr']) && $authinfo['a_admin_user'] >= 2) {
		    $sql_a1 .= ', a_admin_user';
		    $sql_a2 .= ', '.(max(0, min(intval($_POST['a_a_usr']), $authinfo['a_admin_user'])));
		}
		if(isset($_POST['a_super']) && is_numeric($_POST['a_super']) && $authinfo['a_super'] >= 2) {
		    $sql_a1 .= ', a_super';
		    $sql_a2 .= ', '.(max(0, min(intval($_POST['a_super']), $authinfo['a_super'])));
		}

		$sql = 'INSERT INTO '.$cfg['tablenames']['user'].' (mbox, person, pate, canonical, reg_exp, domains, max_alias, max_regexp, created'.$sql_a1.') ';
		$sql .= 'VALUES (\''.mysql_escape_string($_POST['mbox']).'\',\''.mysql_escape_string($_POST['person']).'\',\''.mysql_escape_string($_POST['pate']).'\',\''.mysql_escape_string($_POST['canonical']).'\',\''.mysql_escape_string($_POST['reg_exp']).'\',\''.mysql_escape_string($_POST['domains']).'\','.intval($_POST['max_alias']).','.intval($_POST['max_regexp']).', now()'.$sql_a2.')';
		mysql_query($sql);
		if(mysql_affected_rows() < 1) {
		    error(mysql_error());
		    break;
		}
		// decrease current users's contingents
		if($authinfo['a_super'] == 0) {
		    mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['user'].' SET max_alias='.($cuser['max_alias']-intval($_POST['max_alias'])).', max_regexp='.($cuser['max_regexp']-intval($_POST['max_regexp'])).' WHERE mbox=\''.$cuser['mbox'].'\' LIMIT 1');
		}
		// ... and then, on success, create the user in cyrus
		$result = $cyr->createmb(cyrus_format_user($_POST['mbox']));
		if($result) {
		    $error(var_export($result, true));
		    // Rollback
		    mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['user'].' SET max_alias='.$cuser['max_alias'].', max_regexp='.$cuser['max_regexp'].' WHERE mbox=\''.$cuser['mbox'].'\' LIMIT 1');
		    mysql_unbuffered_query('DELETE FROM '.$cfg['tablenames']['user'].' WHERE mbox=\''.mysql_escape_string($_POST['mbox']).'\' LIMIT 1');
		    if($cfg['create_canonical'])
			mysql_unbuffered_query('DELETE FROM '.$cfg['tablenames']['virtual'].' WHERE address=\''.mysql_escape_string($_POST['canonical']).'\' LIMIT 1');
		    break;
		}
		else {
		    mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['user'].' SET mbox_exists=1 WHERE mbox=\''.mysql_escape_string($_POST['mbox']).'\' LIMIT 1');
		    if(isset($cfg['folders']['create_default']) && is_array($cfg['folders']['create_default'])) {
			foreach($cfg['folders']['create_default'] as $key => $new_folder) {
			    $cyr->createmb(cyrus_format_user($_POST['mbox'], $new_folder));
			}
			unset($new_folder); unset($key);
		    }
		}
		// (define quota)
		if($authinfo['a_super'] == 0 && hsys_getMaxQuota($cuser['mbox']) != 'NOT-SET') {
		    $result = $cyr->setmbquota(cyrus_format_user($cuser['mbox']), hsys_getMaxQuota($cuser['mbox'])-$_POST['quota']);
		    if($result) {
			error(var_export($result, true));
			// Rollback
			$cyr->deletemb(cyrus_format_user($_POST['mbox']));
			mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['user'].' SET max_alias='.$cuser['max_alias'].', max_regexp='.$cuser['max_regexp'].' WHERE mbox=\''.$cuser['mbox'].'\' LIMIT 1');
			mysql_unbuffered_query('DELETE FROM '.$cfg['tablenames']['user'].' WHERE mbox=\''.mysql_escape_string($_POST['mbox']).'\' LIMIT 1');
			if($cfg['create_canonical'])
			    mysql_unbuffered_query('DELETE FROM '.$cfg['tablenames']['virtual'].' WHERE address=\''.mysql_escape_string($_POST['canonical']).'\' LIMIT 1');
			break;
		    }
		    info(sprintf(txt('69'), hsys_getMaxQuota($cuser['mbox'])-$_POST['quota']));
		}
		else {
		    info(txt('71'));
		}

		if(is_numeric($_POST['quota'])) {
		    $result = $cyr->setmbquota(cyrus_format_user($_POST['mbox']), $_POST['quota']);
		    if($result) {
			error(var_export($result, true));
			// Rollback
			$cyr->deletemb(cyrus_format_user($_POST['mbox']));
			if($authinfo['a_super'] == 0 && hsys_getMaxQuota($cuser['mbox']) != 'NOT-SET')
			    $cyr->setmbquota(cyrus_format_user($cuser['mbox']), hsys_getMaxQuota($cuser['mbox']));
			mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['user'].' SET max_alias='.$cuser['max_alias'].', max_regexp='.$cuser['max_regexp'].' WHERE mbox=\''.$cuser['mbox'].'\' LIMIT 1');
			mysql_unbuffered_query('DELETE FROM '.$cfg['tablenames']['user'].' WHERE mbox=\''.mysql_escape_string($_POST['mbox']).'\' LIMIT 1');
			if($cfg['create_canonical'])
			    mysql_unbuffered_query('DELETE FROM '.$cfg['tablenames']['virtual'].' WHERE address=\''.mysql_escape_string($_POST['canonical']).'\' LIMIT 1');
			break;
		    }
		}
		info(sprintf(txt('72'), B($_POST['mbox']), B($_POST['person'])));
		if(isset($_SESSION['paten'][$_POST['pate']])) {
		    $_SESSION['paten'][$_POST['pate']][] = $_POST['mbox'];
		}
		break;
	    case 'delete':
		$aux_tmp = implode(',', $_POST['user']);
		if($authinfo['a_super'] == 0) {
		    $result = mysql_query('SELECT SUM(max_alias) AS nr_alias, SUM(max_regexp) AS nr_regexp FROM '.$cfg['tablenames']['user'].' WHERE FIND_IN_SET(mbox, \''.$aux_tmp.'\')');
		    if(mysql_num_rows($result) > 0) {
			$tmp = mysql_fetch_assoc($result);
			mysql_free_result($result);
		    }
		}
		// delete from cyrus
		foreach($_POST['user'] as $key=>$user) {
		    if($user != '') {
			if($authinfo['a_super'] == 0) {
			    if(hsys_getMaxQuota($user) != 'NOT-SET')
				$add_quota += hsys_getMaxQuota($user);
			}
			$result = $cyr->deletemb(cyrus_format_user($user));
			if($result) {
			    error(var_export($result, true));
			    break;
			}
		    }
		}

		// virtual
		mysql_query('DELETE FROM '.$cfg['tablenames']['virtual'].' WHERE FIND_IN_SET(owner, \''.$aux_tmp.'\')');
		mysql_query('UPDATE '.$cfg['tablenames']['virtual'].' SET active=0, neu=1 WHERE FIND_IN_SET(dest, \''.$aux_tmp.'\')');
		// virtual.regexp
		mysql_query('DELETE FROM '.$cfg['tablenames']['virtual_regexp'].' WHERE FIND_IN_SET(owner, \''.$aux_tmp.'\')');
		mysql_query('UPDATE '.$cfg['tablenames']['virtual_regexp'].' SET active=0, neu=1 WHERE FIND_IN_SET(dest, \''.$aux_tmp.'\')');
		// domain (if the one to be deleted owns domains, the deletor will inherit them)
		mysql_query('UPDATE '.$cfg['tablenames']['domains'].' SET owner=\''.$cuser['mbox'].'\' WHERE FIND_IN_SET(owner, \''.$aux_tmp.'\')');
		// user
		mysql_query('DELETE FROM '.$cfg['tablenames']['user'].' WHERE FIND_IN_SET(mbox, \''.$aux_tmp.'\')');
		if($authinfo['a_super'] == 0 && isset($tmp['nr_alias'])) {
		    mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['user'].' SET max_alias='.($cuser['max_alias']+$tmp['nr_alias']).', max_regexp='.($cuser['max_regexp']+$tmp['nr_regexp']).' WHERE mbox=\''.$cuser['mbox'].'\' LIMIT 1');
		    unset($tmp);
		}
		// patenkinder (will be inherited by the one deleting)
		mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['user'].' SET pate=\''.$cuser['mbox'].'\' WHERE FIND_IN_SET(pate, \''.$aux_tmp.'\')');

		info(txt('75').$aux_tmp.'. ');
		if($authinfo['a_super'] == 0 && isset($add_quota) && hsys_getMaxQuota($cuser['mbox']) != 'NOT-SET') {
		    $cyr->setmbquota(cyrus_format_user($cuser['mbox']), hsys_getMaxQuota($cuser['mbox'])+$add_quota);
		    info(sprintf(txt('76'), (hsys_getMaxQuota($cuser['mbox'])+$add_quota)));
		}
		unset($user); unset($aux_tmp); unset($key);
		if(isset($_SESSION['paten'])) unset($_SESSION['paten']); // inefficient, but maybe we come up with something more elegant
		break;
	    case 'change':
		$aux_tmp = implode(',', $_POST['user']);
		if(count($_POST['user']) == 1) {
		    if(isset($_POST['c_person']) && $_POST['c_person'] == 1)
			$to_change[]	= 'person = \''.mysql_escape_string($_POST['person']).'\'';
		    if(isset($_POST['c_canon']) && $_POST['c_canon'] == 1)
			$to_change[]	= 'canonical = \''.mysql_escape_string($_POST['canonical']).'\'';
		}
		if(isset($_POST['c_pate']) && $_POST['c_pate'] == 1)
		    $to_change[]	= 'pate = \''.mysql_escape_string($_POST['pate']).'\'';
		if(isset($_POST['c_domains']) && $_POST['c_domains'] == 1)
		    $to_change[]	= 'domains = \''.mysql_escape_string($_POST['domains']).'\'';
		if(isset($_POST['c_alias']) && $_POST['c_alias'] == 1)
		    $to_change[]	= 'max_alias = '.intval($_POST['max_alias']);
		if(isset($_POST['c_regexp']) && $_POST['c_regexp'] == 1)
		    $to_change[]	= 'max_regexp = '.intval($_POST['max_regexp']);
		if(isset($_POST['c_reg_exp']) && $_POST['c_reg_exp'] == 1)
		    $to_change[]	= 'reg_exp = \''.mysql_escape_string($_POST['reg_exp']).'\'';

		if(isset($_POST['c_a_dom']) && $_POST['c_a_dom'] == 1 && is_numeric($_POST['a_a_dom']) && $authinfo['a_admin_domains'] >= 2) {
		    $to_change[]	= 'a_admin_domains = '.(max(0, min(intval($_POST['a_a_dom']), $authinfo['a_admin_domains'])));
		}
		if(isset($_POST['c_a_usr']) && $_POST['c_a_usr'] == 1 && is_numeric($_POST['a_a_usr']) && $authinfo['a_admin_user'] >= 2) {
		    $to_change[]	= 'a_admin_user = '.(max(0, min(intval($_POST['a_a_usr']), $authinfo['a_admin_user'])));
		}
		if(isset($_POST['c_super']) && $_POST['c_super'] == 1 && is_numeric($_POST['a_super']) && $authinfo['a_super'] >= 2) {
		    $to_change[]	= 'a_super = '.(max(0, min(intval($_POST['a_super']), $authinfo['a_super'])));
		}

		if($authinfo['a_super'] == 0 && $_POST['c_alias'] + $_POST['c_regexp'] > 0) {
		    $result = mysql_query('SELECT count(*) AS anzahl, SUM(max_alias) AS nr_alias, SUM(max_regexp) AS nr_regexp FROM '.$cfg['tablenames']['user'].' WHERE FIND_IN_SET(mbox, \''.$aux_tmp.'\')');
		    if(mysql_num_rows($result) > 0) {
			$tmp = mysql_fetch_assoc($result);
			mysql_free_result($result);
			mysql_query('UPDATE '.$cfg['tablenames']['user'].' SET max_alias='.($cuser['max_alias']-$tmp['anzahl']*$_POST['max_alias']+$tmp['nr_alias']).', max_regexp='.($cuser['max_regexp']-$tmp['anzahl']*intval($_POST['max_regexp'])+$tmp['nr_regexp']).' WHERE mbox=\''.$cuser['mbox'].'\' LIMIT 1');
			unset($tmp);
		    }
		}
		if(isset($to_change) && is_array($to_change))
		    mysql_query('UPDATE '.$cfg['tablenames']['user'].' SET '.implode(',', $to_change).' WHERE FIND_IN_SET(mbox, \''.$aux_tmp.'\') LIMIT '.count($_POST['user']));
		unset($to_change);

		if(isset($_POST['c_quota']) && $_POST['c_quota'] == 1) {
		    if($authinfo['a_super'] == 0) {
			foreach($_POST['user'] as $key=>$user) {
			    if($user != '') {
				if(hsys_getMaxQuota($user) == intval($_POST['quota']))
				    continue;
				if(hsys_getMaxQuota($user) != 'NOT-SET')
				    $add_quota += intval($_POST['quota']) - hsys_getMaxQuota($user);
			    }
			}
			if(isset($add_quota) && hsys_getMaxQuota($cuser['mbox']) != 'NOT-SET') {
			    $cyr->setmbquota(cyrus_format_user($cuser['mbox']), hsys_getMaxQuota($cuser['mbox'])-$add_quota);
			    info(sprintf(txt('78'), (hsys_getMaxQuota($cuser['mbox'])-$add_quota)));
			}
		    }
		    reset($_POST['user']);
		    foreach($_POST['user'] as $key=>$user) {
			if($user != '') {
			    $result = $cyr->setmbquota(cyrus_format_user($user), intval($_POST['quota']));
			    if($result) {
				error(var_export($result, true));
			    }
			}
		    }
		    unset($key); unset($value);
		}

		if(isset($_POST['c_mbox']) && $_POST['c_mbox'] == 1) {
		    if($cyr->renamemb(cyrus_format_user($_POST['user']['0']), cyrus_format_user($_POST['mbox']))) {
			mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['user'].' SET mbox = \''.$_POST['mbox'].'\' WHERE mbox = \''.$_POST['user']['0'].'\' LIMIT 1');
			mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['domains'].' SET owner = \''.$_POST['mbox'].'\' WHERE owner = \''.$_POST['user']['0'].'\'');
			mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['domains'].' SET a_admin = REPLACE(a_admin, \''.$_POST['user']['0'].'\', \''.$_POST['mbox'].'\') WHERE a_admin LIKE \'%'.$_POST['user']['0'].'%\'');
			mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual'].' SET dest = REPLACE(dest, \''.$_POST['user']['0'].'\', \''.$_POST['mbox'].'\'), neu = 1 WHERE dest REGEXP \''.$_POST['user']['0'].'[^@]{1,}\' OR dest LIKE \'%'.$_POST['user']['0'].'\'');
			mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual'].' SET owner = \''.$_POST['mbox'].'\' WHERE owner = \''.$_POST['user']['0'].'\'');
			mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual_regexp'].' SET dest = REPLACE(dest, \''.$_POST['user']['0'].'\', \''.$_POST['mbox'].'\'), neu = 1 WHERE dest REGEXP \''.$_POST['user']['0'].'[^@]{1,}\' OR dest LIKE \'%'.$_POST['user']['0'].'\'');
			mysql_unbuffered_query('UPDATE LOW_PRIORITY '.$cfg['tablenames']['virtual_regexp'].' SET owner = \''.$_POST['mbox'].'\' WHERE owner = \''.$_POST['user']['0'].'\'');
		    }
		    else {
			error($cyr->error_msg.'<br />'.txt('94'));
		    }
		}

		if(isset($_SESSION['paten']) && isset($_POST['c_mbox']) && isset($_POST['c_pate'])
		    && ($_POST['c_mbox'] == 1 || $_POST['c_pate'] == 1)) {
		    unset($_SESSION['paten']);	// again: inefficient, but maybe we come up with something more elegant
		}
		break;
	    case 'active':
		mysql_query('UPDATE '.$cfg['tablenames']['user'].' SET active = NOT active WHERE FIND_IN_SET(mbox, \''.implode(',', $_POST['user']).'\') LIMIT '.count($_POST['user']));
		if(mysql_affected_rows() < 1)
		    error(mysql_error());
		break;
	}
    }
}

// DATA
if($cuser['mbox'] == $authinfo['mbox'] && $authinfo['a_super'] >= 1)
    $result = mysql_query('SELECT SQL_CALC_FOUND_ROWS mbox, person, canonical, pate, max_alias, max_regexp, active, date(last_login) AS lastlogin, a_super, a_admin_domains, a_admin_user, (SELECT count(*) FROM '.$cfg['tablenames']['virtual'].' WHERE '.$cfg['tablenames']['virtual'].'.owner=mbox) AS num_alias, (SELECT count(*) FROM '.$cfg['tablenames']['virtual_regexp'].' WHERE '.$cfg['tablenames']['virtual_regexp'].'.owner=mbox) AS num_regexp FROM '.$cfg['tablenames']['user'].' WHERE TRUE'.$_SESSION['filter']['str']['mbox'].' ORDER BY pate, mbox'.$_SESSION['limit']['str']['mbox']);
else
    $result = mysql_query('SELECT SQL_CALC_FOUND_ROWS mbox, person, canonical, pate, max_alias, max_regexp, active, date(last_login) AS lastlogin, a_super, a_admin_domains, a_admin_user, (SELECT count(*) FROM '.$cfg['tablenames']['virtual'].' WHERE '.$cfg['tablenames']['virtual'].'.owner=mbox) AS num_alias, (SELECT count(*) FROM '.$cfg['tablenames']['virtual_regexp'].' WHERE '.$cfg['tablenames']['virtual_regexp'].'.owner=mbox) AS num_regexp FROM '.$cfg['tablenames']['user'].' WHERE pate=\''.$cuser['mbox'].'\''.$_SESSION['filter']['str']['mbox'].' ORDER BY pate, mbox'.$_SESSION['limit']['str']['mbox']);
$mailboxes = array();
$cuser['n_mbox'] = 0;
if(mysql_num_rows($result) > 0) {
    $result2 = mysql_query('SELECT FOUND_ROWS()');
    $cuser['n_mbox'] = mysql_result($result2, 0, 0);
    mysql_free_result($result2);
    while($row = mysql_fetch_assoc($result)) {
	if($row['mbox'] == 'cyrus')
	    continue;

	$row['quota'] = hsys_format_quota($row['mbox']);
	$mailboxes[] = $row;
    }
    mysql_free_result($result);
}
// DISPLAY
include('templates/'.$cfg['theme'].'/mailboxes/list.tpl');

// ADMIN PANEL
if($authinfo['a_admin_user'] >= 1) {
    // what paten may he select?
    if(!isset($_SESSION['paten'][$cuser['mbox']])) {
	$selectable_paten = array();
	$result = mysql_query('SELECT mbox FROM '.$cfg['tablenames']['user'].($authinfo['a_super'] == 0 ? ' WHERE pate=\''.$cuser['mbox'].'\'' : ''));
	while($row = mysql_fetch_assoc($result)) {
	    $selectable_paten[] = $row['mbox'];
	}
	mysql_free_result($result);
	$selectable_paten[] = $cuser['mbox'];
	$selectable_paten[] = $authinfo['mbox'];
	$_SESSION['paten'][$cuser['mbox']] = array_unique($selectable_paten);	// this will sort the array
    }

    include('templates/'.$cfg['theme'].'/mailboxes/admin.tpl');
}

$cyr->imap_logout();

include('inc/_append.php4');
?>