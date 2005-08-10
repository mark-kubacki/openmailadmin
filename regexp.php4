<?php
include('inc/_prepend.php4');
include('inc/panel_filter.php4');

$cuser['domain_set'] = getDomainSet($cuser['mbox'], $cuser['domains']);
$cuser['used_regexp'] = hsys_getUsedRegexp($cuser['mbox']);

// ------------------------------ Regexp ----------------------------------------------------------
// PERFORM ACTION
if(isset($_POST['frm']) && $_POST['frm'] == 'virtual_regexp') {
    if(!isset($_POST['action'])) {
	error(txt('112'));
    }
    else {
	if($cuser['max_regexp'] != 0 && $authinfo['max_regexp'] != 0) {
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
		    preg_match_all('/((?:[A-Za-z0-9][A-Za-z0-9\.\-\_\+\$]{1,}@[A-Za-z0-9\.\-\_\$]{2,}\.[A-Za-z]{2,})'.$tmp_subpattern.')/', $_POST['dest'], $arr_dest);
		    if(isset($arr_dest['1']['0']))
			$destination = implode(', ', $arr_dest[1]);
		    else {
			$destination = $cuser['mbox'];
			error(txt('10'));
		    }
		}
	    }
	    if(isset($_POST['expr']) && !is_array($_POST['expr']) && $_POST['action'] != 'new' && $_POST['action'] != 'probe') {
		error(txt('11'));
	    }
	    else
	    switch($_POST['action']) {
		case 'new':
		    if($cuser['used_regexp'] < $cuser['max_regexp'] || $authinfo['a_super'] >= 1) {
			$_POST['reg_exp'] = trim($_POST['reg_exp']);
			mysql_query('INSERT INTO '.$cfg['tablenames']['virtual_regexp'].' (reg_exp, dest, owner) VALUES (\''.mysql_escape_string($_POST['reg_exp']).'\', \''.$destination.'\', \''.$cuser['mbox'].'\')');
			if(mysql_affected_rows() < 1)
			    error(mysql_error());
			else
			    $cuser['used_regexp']++;
		    }
		    else
			error(txt('31'));
		    unset($tmp);
		    break;
		case 'delete':
		    mysql_query('DELETE FROM '.$cfg['tablenames']['virtual_regexp'].' WHERE owner = \''.$cuser['mbox'].'\' AND FIND_IN_SET(ID, "'.mysql_escape_string(implode(',', $_POST['expr'])).'") LIMIT '.count($_POST['expr']));
		    if(mysql_affected_rows() < 1)
			error(mysql_error());
		    else {
			info(txt('32'));
			$cuser['used_regexp'] -= mysql_affected_rows();
		    }
		    break;
		case 'dest':
		    mysql_query('UPDATE '.$cfg['tablenames']['virtual_regexp'].' SET dest = "'.$destination.'", neu = 1 WHERE owner = \''.$cuser['mbox'].'\' AND FIND_IN_SET(ID, "'.mysql_escape_string(implode(',', $_POST['expr'])).'") LIMIT '.count($_POST['expr']));
		    if(mysql_affected_rows() < 1)
			error(mysql_error());
		    break;
		case 'active':
		    mysql_query('UPDATE '.$cfg['tablenames']['virtual_regexp'].' SET active = NOT active, neu = 1 WHERE owner = \''.$cuser['mbox'].'\' AND FIND_IN_SET(ID, "'.mysql_escape_string(implode(',', $_POST['expr'])).'") LIMIT '.count($_POST['expr']));
		    if(mysql_affected_rows() < 1)
			error(mysql_error());
		    break;
	    }
	    if(isset($destination)) unset($destination);
	}
	else
	    error(txt('16'));
    }
}

// DATA
$result = mysql_query('SELECT * FROM '.$cfg['tablenames']['virtual_regexp'].' WHERE owner=\''.$cuser['mbox'].'\''.$_SESSION['filter']['str']['regexp'].' ORDER BY dest'.$_SESSION['limit']['str']['regexp']);
$regexp = array();
if(mysql_num_rows($result) > 0) {
    while($row = mysql_fetch_assoc($result)) {
	// if ordered, check whether expression matches probe address
	if(isset($_POST['frm']) && isset($_POST['action'])
	    && $_POST['frm'] == 'virtual_regexp' && $_POST['action'] == 'probe'
	    && @preg_match($row['reg_exp'], $_POST['probe'])) {
	    $row['matching']	= true;
	}
	else {
	    $row['matching']	= false;
	}
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
	// add the current entry to our list of aliases
	$regexp[] = $row;
    }
    mysql_free_result($result);
}
// DISPLAY
include('templates/'.$cfg['theme'].'/regexp/list.tpl');

// ADMIN PANEL
if($cuser['max_regexp'] != 0 && $authinfo['max_regexp'] != 0) {
    if(isset($_POST['frm']) && isset($_POST['action']) && $_POST['frm'] == 'virtual_regexp' && $_POST['action'] == 'probe' && trim($_POST['reg_exp']) != '' && trim($_POST['probe']) != '' && @preg_match($_POST['reg_exp'], $_POST['probe'])) {
	info(txt('36'));
    }

    include('templates/'.$cfg['theme'].'/regexp/admin.tpl');
}

include('inc/_append.php4');
?>