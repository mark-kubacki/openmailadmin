<?php
/* Fake cyradm
 *   use in demo-accounts
 */

class cyradm {
    var $error_msg	= 'This is not an IMAP server - just a fake. Do not worry about any errors.';

    function imap_login() {
	return true;
    }

    function imap_logout() {
	return true;
    }

    function command($line) {
	global $cfg;
	global $oma;

	switch($line) {
	    case '. list "" *':
		// query for all visible folders
		$ret = array();
		$result = mysql_query('SELECT mailbox as folder,'
			.' (SELECT COUNT(*) FROM '.$cfg['tablenames']['imap_demo'].' WHERE mailbox LIKE CONCAT(folder, ".%")) AS children'
			.' FROM '.$cfg['tablenames']['imap_demo']
			.' WHERE ACL LIKE "% '.$oma->current_user['mbox'].' l%" OR ACL LIKE "'.$oma->current_user['mbox'].' l%" OR ACL LIKE "% anyone l%" OR ACL LIKE "anyone l%"');
		if(mysql_num_rows($result) > 0) {
		    while($row = mysql_fetch_assoc($result)) {
			$ret[] = '* LIST '
				.($row['children'] == 0 ? '(\HasNoChildren)' : '(\HasChildren)')
				.' "." "'.$row['folder'].'"';
		    }
		    // $ret[] = '. OK Completed (0.003 secs '.mysql_num_rows($result).' calls)';
		    mysql_free_result($result);
		}
		return $ret;
		break;
	    case '. list "" ""':
		return array(	'* LIST (\Noselect) "." ""',
				'. OK Completed (0.000 secs 0 calls)'
			    );
		break;
	    default:
		trigger_error('Unknown command in fake-cyradm: "'.$line.'" - please report.');
		return array();
		break;
	}
    }

    function createmb($mb) {
	global $cfg;
	global $oma;

	if(isset($_GET['folder'])) {
	    $result = mysql_query('SELECT ACL FROM '.$cfg['tablenames']['imap_demo'].' WHERE mailbox="'.mysql_escape_string($_GET['folder']).'" LIMIT 1');
	    $newacl = mysql_result($result, 0, 0);
	    mysql_free_result($result);
	    $acl = $this->getacl(mysql_escape_string($_GET['folder']));
	    if(isset($acl[$oma->current_user['mbox']]) && stristr($acl[$oma->current_user['mbox']], 'a')
		|| isset($acl['anyone']) && stristr($acl['anyone'], 'a')) {
		mysql_query('INSERT INTO '.$cfg['tablenames']['imap_demo'].' (mailbox, ACL) VALUES ("'.$mb.'", "'.$newacl.'")');
	    }
	    else {
		return array('* BAD');
	    }
	}
	else if(isset($_POST['mbox'])) {
	    mysql_query('INSERT INTO '.$cfg['tablenames']['imap_demo'].' (mailbox, ACL) VALUES ("'.$mb.'", "'.$_POST['mbox'].' lrswipcda")');
	}
	else {
	    mysql_query('INSERT INTO '.$cfg['tablenames']['imap_demo'].' (mailbox, ACL) VALUES ("'.$mb.'", "'.$oma->current_user['mbox'].' lrswipcda")');
	}
	if(mysql_affected_rows() < 1) {
	    return array(mysql_error());
	}
	else {
	    return array();
	}
    }

    function deletemb($mb) {
	global $cfg;
	mysql_query("DELETE FROM ".$cfg['tablenames']['imap_demo']." WHERE mailbox='$mb' OR mailbox LIKE '$mb.%'");
	if(mysql_affected_rows() < 1) {
	    return array(mysql_error());
	}
	else {
	    return array();
	}
    }

    function renamemb($from_mb, $to_mb) {
	global $cfg;
	mysql_query('UPDATE '.$cfg['tablenames']['imap_demo']
			.' SET mailbox=REPLACE(mailbox, "'.$from_mb.'", "'.$to_mb.'"), '
				.'ACL=REPLACE(ACL, "'.str_replace('user.', '', $from_mb).'", "'.str_replace('user.', '', $to_mb).'")'
			.' WHERE mailbox = "'.$from_mb.'" OR mailbox LIKE "'.$from_mb.'%"');
	if(mysql_error() != '') {
	    return array(mysql_error());
	}
	else {
	    return array();
	}
    }

    function setmbquota($mb, $many) {
	global $cfg;
	if(is_numeric($many))
	    mysql_query('UPDATE '.$cfg['tablenames']['imap_demo'].' SET qmax='.max(1, $many).', used=FLOOR(RAND()*'.max(1, $many).') WHERE mailbox=\''.$mb.'\' LIMIT 1');
	else
	    mysql_query('UPDATE '.$cfg['tablenames']['imap_demo'].' SET qmax=0 WHERE mailbox=\''.$mb.'\' LIMIT 1');
	if(mysql_affected_rows() < 1) {
	    return array(mysql_error());
	}
	else {
	    return array();
	}
    }

    function getquota($mb) {
	global $cfg;
	$result = mysql_query("SELECT qmax,used FROM ".$cfg['tablenames']['imap_demo']." WHERE mailbox='$mb' LIMIT 1");
	if(mysql_num_rows($result) < 1) {
	    return array('No quota for this mailbox found!');
	}
	else {
	    $row = mysql_fetch_assoc($result);
	    mysql_free_result($result);
	    if($row['qmax'] == 0) {
		return array('qmax' => 'NOT-SET', 'used' => 'NOT-SET');
	    }
	    return $row;
	}
    }

    function setacl($mb, $user, $acl) {
	global $cfg;
	global $oma;

	// does the user exist?
	$result = mysql_query('SELECT * FROM '.$cfg['tablenames']['user'].' WHERE mbox="'.mysql_escape_string($user).'" LIMIT 1');
	if(mysql_num_rows($result) > 0) {
	    $user = mysql_result($result, 0, 0);
	    mysql_free_result($result);

	    // fetch old ACL
	    $facl = $this->getacl(mysql_escape_string($mb));
	    if(isset($facl[$oma->current_user['mbox']]) && stristr($facl[$oma->current_user['mbox']], 'a')
		|| isset($facl['anyone']) && stristr($facl['anyone'], 'a')) {
		// modify ACL
		if($acl == 'none') {
		    unset($facl[$user]);
		}
		else {
		    $facl[$user] = mysql_escape_string(trim($acl));	// we can be lax in th demo
		}
		// unify keys and values for storage
                $store = '';
                foreach($facl as $user=>$rgh) {
		    $store .= $user.' '.$rgh.' ';
		}

		// write to MySQL
                mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['imap_demo'].' SET ACL="'.trim($store).'" WHERE mailbox="'.mysql_escape_string($mb).'" LIMIT 1');
	    }
	}
    }

    function getacl($mb) {
	global $cfg;

	$result = mysql_query("SELECT ACL FROM ".$cfg['tablenames']['imap_demo']." WHERE mailbox='$mb' LIMIT 1");
	if(mysql_num_rows($result) < 1) {
	    return array();
	}
	else {
	    $acl = mysql_result($result, 0, 0);
	    mysql_free_result($result);
	    return hsys_getACLInfo(array('* ACL '.$mb.' '.$acl), $mb);
	}
    }

    function getversion() {
	return '2.2.12';
    }
};

?>