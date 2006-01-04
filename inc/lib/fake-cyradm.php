<?php
/**
 * For emulating cyrus.php by utilizing database as storage.
 */
class fake_imap
{
	var $connection_data;
	var $db;
	var $error_msg	= '';
	var $separator	= '.';

	function fake_imap($connection_data, $adodb_handler) {
		$this->connection_data	= $connection_data;
		$this->db		= $adodb_handler;
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
				$result = $this->db->Execute('SELECT mailbox as folder,'
					.' (SELECT COUNT(*) FROM '.$cfg['tablenames']['imap_demo'].' WHERE mailbox LIKE CONCAT(folder, '.$this->db->qstr('.%').')) AS children'
					.' FROM '.$cfg['tablenames']['imap_demo']
					.' WHERE ACL LIKE '.$this->db->qstr('% '.$oma->current_user['mbox'].' l%').' OR ACL LIKE '.$this->db->qstr($oma->current_user['mbox'].' l%').' OR ACL LIKE '.$this->db->qstr('% anyone l%').' OR ACL LIKE '.$this->db->qstr('anyone l%'));
				if(!$result === false) {
					while(!$result->EOF) {
						$ret[] = '* LIST '
						.($result->fields['children'] == 0 ? '(\HasNoChildren)' : '(\HasChildren)')
						.' "." "'.$result->fields['folder'].'"';
						$result->MoveNext();
					}
					// $ret[] = '. OK Completed (0.003 secs '.mysql_num_rows($result).' calls)';
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
			$newacl = $this->db->GetOne('SELECT ACL FROM '.$cfg['tablenames']['imap_demo'].' WHERE mailbox='.$this->db->qstr($_GET['folder']));
			$acl = $this->getacl($_GET['folder']);
			if(isset($acl[$oma->current_user['mbox']]) && stristr($acl[$oma->current_user['mbox']], 'a')
			   || isset($acl['anyone']) && stristr($acl['anyone'], 'a')) {
				$this->db->Execute('INSERT INTO '.$cfg['tablenames']['imap_demo'].' (mailbox, ACL) VALUES (?,?)', array($mb, $newacl));
			} else {
				$this->error_msg = 'You need "a"-rights on that mailbox.';
				return false;
			}
		} else if(isset($_POST['mbox'])) {
			$this->db->Execute('INSERT INTO '.$cfg['tablenames']['imap_demo'].' (mailbox, ACL) VALUES (?,?)', array($mb, $_POST['mbox'].' lrswipcda'));
		} else {
			$this->db->Execute('INSERT INTO '.$cfg['tablenames']['imap_demo'].' (mailbox, ACL) VALUES (?,?)', array($mb, $oma->current_user['mbox'].' lrswipcda'));
		}
		if($this->db->Affected_Rows() < 1) {
			$this->error_msg	= $this->db->ErrorMsg();
			return false;
		} else {
			return true;
		}
	}

	function deletemb($mb) {
		global $cfg;
		$this->db->Execute('DELETE FROM '.$cfg['tablenames']['imap_demo'].' WHERE mailbox='.$this->db->qstr($mb).' OR mailbox LIKE '.$this->db->qstr($mb.'%'));
		if($this->db->Affected_Rows() < 1) {
			return false;
		}
		return true;
	}

	function renamemb($from_mb, $to_mb) {
		global $cfg;
		$this->db->Execute('UPDATE '.$cfg['tablenames']['imap_demo']
			.' SET mailbox=REPLACE(mailbox, '.$this->db->qstr($from_mb).', '.$this->db->qstr($to_mb).'), '
				.'ACL=REPLACE(ACL, '.$this->db->qstr(str_replace('user.', '', $from_mb)).', '.$this->db->qstr(str_replace('user.', '', $to_mb)).')'
			.' WHERE mailbox = '.$this->db->qstr($from_mb).' OR mailbox LIKE '.$this->db->qstr($from_mb.'%'));
		if($this->db->ErrorMsg() != '') {
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
			$this->db->Execute('UPDATE '.$cfg['tablenames']['imap_demo'].' SET qmax='.intval(max(1, $many)).', used=FLOOR(RAND()*'.intval(max(1, $many)).') WHERE mailbox='.$this->db->qstr($mb).' LIMIT 1');
		} else if(is_null($many)) {
			$this->db->Execute('UPDATE '.$cfg['tablenames']['imap_demo'].' SET qmax=0 WHERE mailbox='.$this->db->qstr($mb).' LIMIT 1');
		} else {
			$this->error_msg	= 'Quota has either to be numeric or null!';
			return false;
		}
		if($this->db->Affected_Rows() < 1) {
			$this->error_msg	= 'Given mailbox does not exist.';
			return false;
		}

		return true;
	}

	function getquota($mb) {
		global $cfg;
		$row = $this->db->GetRow('SELECT qmax,used FROM '.$cfg['tablenames']['imap_demo'].' WHERE mailbox='.$this->db->qstr($mb));
		if($row === false || !isset($row['qmax'])) {
			$this->error_msg	= 'Given mailbox does not exist.';
			return array();
		} else {
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
		$user = $this->db->GetOne('SELECT mbox FROM '.$cfg['tablenames']['user'].' WHERE mbox='.$this->db->qstr($user));
		if(!$user === false) {
			// fetch old ACL
			$facl = $this->getacl($mb);
			if(isset($facl[$oma->current_user['mbox']]) && stristr($facl[$oma->current_user['mbox']], 'a')
			   || isset($facl['anyone']) && stristr($facl['anyone'], 'a')) {
				// modify ACL
				if($acl == 'none') {
					unset($facl[$user]);
				} else {
					$facl[$user] = trim($acl);
				}
				// unify keys and values for storage
				$store = '';
				foreach($facl as $user=>$rgh) {
					$store .= $user.' '.$rgh.' ';
				}

				// write to MySQL
				$this->db->Execute('UPDATE '.$cfg['tablenames']['imap_demo'].' SET ACL='.$this->db->qstr(trim($store)).' WHERE mailbox='.$this->db->qstr($mb).' LIMIT 1');
				return true;
			}
		} else {
			$this->error_msg	= 'User does not exist.';
		}
		return false;
	}

	function getacl($mb) {
		global $cfg;

		$acl = $this->db->GetOne('SELECT ACL FROM '.$cfg['tablenames']['imap_demo'].' WHERE mailbox='.$this->db->qstr($mb).' LIMIT 1');
		if($acl === false) {
			return array();
		} else {
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