<?php
/* Fake cyradm
 *   use in demo-accounts
 */
class imapd_adm {
	var $connection_data;
	var $error_msg	= '';
	var $separator	= '.';

	function imapd_adm($connection_data) {
		$this->connection_data	= $connection_data;
	}

	function imap_login() {
		return true;
	}

	function imap_logout() {
		return true;
	}

	function gethierarchyseparator() {
		return $this->separator;
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
			} else {
				$this->error_msg = 'You need "a"-rights on that mailbox.';
				return false;
			}
		} else if(isset($_POST['mbox'])) {
			mysql_query('INSERT INTO '.$cfg['tablenames']['imap_demo'].' (mailbox, ACL) VALUES ("'.$mb.'", "'.$_POST['mbox'].' lrswipcda")');
		} else {
			mysql_query('INSERT INTO '.$cfg['tablenames']['imap_demo'].' (mailbox, ACL) VALUES ("'.$mb.'", "'.$oma->current_user['mbox'].' lrswipcda")');
		}
		if(mysql_affected_rows() < 1) {
			$this->error_msg	= mysql_error();
			return false;
		} else {
			return true;
		}
	}

	function deletemb($mb) {
		global $cfg;
		mysql_query("DELETE FROM ".$cfg['tablenames']['imap_demo']." WHERE mailbox='$mb' OR mailbox LIKE '$mb.%'");
		if(mysql_affected_rows() < 1) {
			return false;
		}
		return true;
	}

	function renamemb($from_mb, $to_mb) {
		global $cfg;
		mysql_query('UPDATE '.$cfg['tablenames']['imap_demo']
			.' SET mailbox=REPLACE(mailbox, "'.$from_mb.'", "'.$to_mb.'"), '
				.'ACL=REPLACE(ACL, "'.str_replace('user.', '', $from_mb).'", "'.str_replace('user.', '', $to_mb).'")'
			.' WHERE mailbox = "'.$from_mb.'" OR mailbox LIKE "'.$from_mb.'%"');
		if(mysql_error() != '') {
			return false;
		}
		return true;
	}

	function getmailboxes($ref = '', $pat = '*') {
		$result = array();

		foreach($this->command('. list "" *') as $folder) {
			if(preg_match('/\*\sLIST\s\((.*)\)\s\"(.*?)\"\s\"(.*?)\"/', $folder, $arr)) {
				$result[]
				= array('attributes'	=> $arr[1],
					'delimiter'	=> $arr[2],
					'name'		=> trim($arr[3]));
			}
		}

		return $result;
	}

	function setquota($mb, $many, $storage = '') {
		global $cfg;
		if(is_numeric($many)) {
			mysql_query('UPDATE '.$cfg['tablenames']['imap_demo'].' SET qmax='.intval(max(1, $many)).', used=FLOOR(RAND()*'.intval(max(1, $many)).') WHERE mailbox="'.$mb.'" LIMIT 1');
		} else if(is_null($many)) {
			mysql_query('UPDATE '.$cfg['tablenames']['imap_demo'].' SET qmax=0 WHERE mailbox="'.$mb.'" LIMIT 1');
		} else {
			$this->error_msg	= 'Quota has either to be numeric or null!';
			return false;
		}
		if(mysql_affected_rows() < 1) {
			$this->error_msg	= 'Given mailbox does not exist.';
			return false;
		}

		return true;
	}

	function getquota($mb) {
		global $cfg;
		$result = mysql_query("SELECT qmax,used FROM ".$cfg['tablenames']['imap_demo']." WHERE mailbox='$mb' LIMIT 1");
		if(mysql_num_rows($result) < 1) {
			$this->error_msg	= 'Given mailbox does not exist.';
			return array();
		} else {
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
				} else {
					$facl[$user] = mysql_escape_string(trim($acl));	// we can be lax in th demo
				}
				// unify keys and values for storage
				$store = '';
				foreach($facl as $user=>$rgh) {
					$store .= $user.' '.$rgh.' ';
				}

				// write to MySQL
				mysql_unbuffered_query('UPDATE '.$cfg['tablenames']['imap_demo'].' SET ACL="'.trim($store).'" WHERE mailbox="'.mysql_escape_string($mb).'" LIMIT 1');
				return true;
			}
		} else {
			$this->error_msg	= 'User does not exist.';
		}
		return false;
	}

	function getacl($mb) {
		global $cfg;

		$result = mysql_query('SELECT ACL FROM '.$cfg['tablenames']['imap_demo']." WHERE mailbox='$mb' LIMIT 1");
		if(mysql_num_rows($result) < 1) {
			return array();
		} else {
			$acl = mysql_result($result, 0, 0);
			mysql_free_result($result);
			return hsys_getACLInfo(array('* ACL '.$mb.' '.$acl), $mb);
		}
	}

	function deleteacl($mb, $user) {
		return $this->setacl($mb, $user, 'none');
	}

	function getversion() {
		return '2.2.12';
	}

	/**
	 * Adds prefixes and suffixes as well as separators to a username
	 */
	function format_user($username, $folder = null) {
		if(is_null($folder)) {
			return('user'.$this->gethierarchyseparator().$username.$this->connection_data['VDOM']);
		} else {
			return($this->format_user($username).$this->gethierarchyseparator().$folder);
		}
	}

}
?>