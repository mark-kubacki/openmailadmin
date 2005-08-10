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
	    // compute value of common dest
	    if(isset($_POST['dest_is_mbox']) && $_POST['dest_is_mbox'] == '1')
		$destination = $cuser['mbox'];
	    else {
		$_POST['dest'] = str_replace(array(txt('5'), strtolower(txt('5'))), array($cuser['mbox'], $cuser['mbox']), $_POST['dest']);
		$tmp_subpattern = '|(?:'.$cuser['mbox'].')';
		if($cfg['allow_mbox_as_target']) {
		    $result = mysql_query('SELECT mbox FROM '.$cfg['tablenames']['user'].' WHERE active = 1 AND mbox_exists = 1');
		    while($row = mysql_fetch_assoc($result)) {
			$tmp[] = $row['mbox'];
		    }
		    mysql_free_result($result);
		    $tmp_subpattern .= '|(?:'.implode('|', $tmp).')';
		    unset($tmp); unset($row);
		}
		else if($cfg['allow_wcyr_as_target']) {
		    $tmp_subpattern .= '|(?:[a-z]{2,}[0-9]{4})';
		}
		preg_match_all('/((?:[A-Za-z0-9][A-Za-z0-9\.\-\_\+]{1,}@[A-Za-z0-9\.\-\_]{2,}\.[A-Za-z]{2,})'.$tmp_subpattern.')/', $_POST['dest'], $arr_dest);
		if(isset($arr_dest['1']['0']))
		    $destination = implode(', ', $arr_dest[1]);
		else {
		    $destination = $cuser['mbox'];
		    error(txt('10'));
		}
	    }
	}
	if(isset($_POST['address']) && !is_array($_POST['address']) && $_POST['action'] != 'new') {
	    error(txt('11'));
	}
	else
	switch($_POST['action']) {
	    case 'new':
		if($cuser['used_alias'] < $cuser['max_alias'] || $authinfo['a_super'] >= 1) {
		    $_POST['alias'] = trim($_POST['alias']);
		    if($_POST['alias'] == '*' && $cfg['address']['allow_catchall']) {
			if($cfg['address']['restrict_catchall']) {
			    // if either cuser or authuser are owner of that given domain, we can create that catchall
			    $result = mysql_query('SELECT domain FROM '.$cfg['tablenames']['domains'].' WHERE domain = "'.mysql_escape_string($_POST['domain']).'" AND (owner="'.$cuser['mbox'].'" OR owner="'.$authinfo['mbox'].'") LIMIT 1');
			    if(mysql_num_rows($result) > 0) {
				mysql_free_result($result);
			    }
			    else {
				error(txt('16'));
				break;
			    }
			}
			mysql_query('INSERT INTO '.$cfg['tablenames']['virtual'].' (address, dest, owner) VALUES (\'@'.$_POST['domain'].'\', \''.$destination.'\', \''.$cuser['mbox'].'\')');
			if(mysql_affected_rows() < 1)
			    error(mysql_error());
			else
			    $cuser['used_alias']++;
		    }
		    else if(preg_match('/([A-Z0-9\.\-\_]{'.strlen($_POST['alias']).'})/i', $_POST['alias'])) {
			if($cuser['reg_exp'] == '' || preg_match($cuser['reg_exp'], $_POST['alias'].'@'.$_POST['domain'])) {
			    mysql_query('INSERT INTO '.$cfg['tablenames']['virtual'].' (address, dest, owner) VALUES (\''.mysql_escape_string($_POST['alias'].'@'.$_POST['domain']).'\', \''.$destination.'\', \''.$cuser['mbox'].'\')');
			    if(mysql_affected_rows() < 1)
				error(mysql_error());
			    else
				$cuser['used_alias']++;
			}
			else
			    error(txt('12'));
		    }
		    else
			error(txt('13'));
		}
		else
		    error(txt('14'));
		unset($tmp);
		break;
	    case 'delete':
		mysql_query('DELETE FROM '.$cfg['tablenames']['virtual'].' WHERE owner = \''.$cuser['mbox'].'\' AND FIND_IN_SET(address, "'.mysql_escape_string(implode(',', $_POST['address'])).'") LIMIT '.count($_POST['address']));
		if(mysql_affected_rows() < 1)
		    error(mysql_error());
		else {
		    info(txt('15').implode(',', $_POST['address']));
		    $cuser['used_alias'] -= mysql_affected_rows();
		}
		break;
	    case 'dest':
		mysql_query('UPDATE '.$cfg['tablenames']['virtual'].' SET dest = "'.$destination.'", neu = 1 WHERE owner = \''.$cuser['mbox'].'\' AND FIND_IN_SET(address, "'.mysql_escape_string(implode(',', $_POST['address'])).'") LIMIT '.count($_POST['address']));
		if(mysql_affected_rows() < 1)
		    error(mysql_error());
		break;
	    case 'active':
		mysql_query('UPDATE '.$cfg['tablenames']['virtual'].' SET active = NOT active, neu = 1 WHERE owner = \''.$cuser['mbox'].'\' AND FIND_IN_SET(address, "'.mysql_escape_string(implode(',', $_POST['address'])).'") LIMIT '.count($_POST['address']));
		if(mysql_affected_rows() < 1)
		    error(mysql_error());
		break;
	}
	unset($destination);
    }
}

// DATA
$result = mysql_query('SELECT address, dest, SUBSTRING_INDEX(address, \'@\', 1) as alias, SUBSTRING_INDEX(address, \'@\', -1) as domain, active FROM '.$cfg['tablenames']['virtual'].' WHERE owner=\''.$cuser['mbox'].'\''.$_SESSION['filter']['str']['address'].' ORDER BY domain, dest, alias'.$_SESSION['limit']['str']['address']);
$alias = array();
if(mysql_num_rows($result) > 0) {
    while($row = mysql_fetch_assoc($result)) {
	// explode all destinations (as there may be many)
	$dest = array();
	foreach(explode(',', $row['dest']) as $key => $value) {
	    $value = trim($value);
	    // replace the current user's name with "mailbox"
	    if($value == $cuser['mbox'])
		$dest[] = txt('5');
	    else
		$dest[] = $value;
	}
	$row['dest'] = $dest;
	//turn the alias of catchalls to a star
	if($row['address']{0} == '@')
	    $row['alias'] = '*';
	// add the current entry to our list of aliases
	$alias[] = $row;
    }
    mysql_free_result($result);
}
// DISPLAY
include('templates/'.$cfg['theme'].'/addresses/list.tpl');

// ADMIN PANEL
include('templates/'.$cfg['theme'].'/addresses/admin.tpl');

include('inc/_append.php4');
?>