<?php
/**
 * For emulating cyrus-imapd client by utilizing database as storage.
 */
class Fake_IMAP
	implements IMAP_Administrator
{
	private	$connection_data;
	private	$db;
	private	$tablenames;
	private	$separator	= '.';

	public	$error_msg	= '';

	function __construct(array $connection_data, ADOConnection $adodb_handler, array $tablenames) {
		$this->connection_data	= $connection_data;
		$this->db		= $adodb_handler;
		$this->tablenames	= $tablenames;
	}

	public function gethierarchyseparator() {
		return $this->separator;
	}

	/**
	 * @param	cmd	Fully formatted command.
	 * @return		Unless additional data is provided the return will be either true or false. On additional data an array will be returned.
	 */
	private function command($line) {
		global $oma;

		switch($line) {
			case '. list "" *':
				// query for all visible folders
				$ret = array();
				$result = $this->db->Execute('SELECT mailbox as folder,'
					.' (SELECT COUNT(*) FROM '.$this->tablenames['imap_demo'].' WHERE mailbox LIKE CONCAT(folder, '.$this->db->qstr('.%').')) AS children'
					.' FROM '.$this->tablenames['imap_demo']
					.' WHERE ACL LIKE '.$this->db->qstr('% '.$oma->current_user->mbox.' l%').' OR ACL LIKE '.$this->db->qstr($oma->current_user->mbox.' l%').' OR ACL LIKE '.$this->db->qstr('% anyone l%').' OR ACL LIKE '.$this->db->qstr('anyone l%'));
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

	public function createmb($mb) {
		global $oma;

		if(isset($_GET['folder'])) {
			$newacl = $this->db->GetOne('SELECT ACL FROM '.$this->tablenames['imap_demo'].' WHERE mailbox='.$this->db->qstr($_GET['folder']));
			$acl = $this->getacl($_GET['folder']);
			if(isset($acl[$oma->current_user->mbox]) && stristr($acl[$oma->current_user->mbox], 'a')
			   || isset($acl['anyone']) && stristr($acl['anyone'], 'a')) {
				$this->db->Execute('INSERT INTO '.$this->tablenames['imap_demo'].' (mailbox, ACL) VALUES (?,?)', array($mb, $newacl));
			} else {
				$this->error_msg = 'You need "a"-rights on that mailbox.';
				return false;
			}
		} else if(isset($_POST['mbox'])) {
			$this->db->Execute('INSERT INTO '.$this->tablenames['imap_demo'].' (mailbox, ACL) VALUES (?,?)', array($mb, $_POST['mbox'].' lrswipcda'));
		} else {
			$this->db->Execute('INSERT INTO '.$this->tablenames['imap_demo'].' (mailbox, ACL) VALUES (?,?)', array($mb, $oma->current_user->mbox.' lrswipcda'));
		}
		if($this->db->Affected_Rows() < 1) {
			$this->error_msg	= $this->db->ErrorMsg();
			return false;
		} else {
			return true;
		}
	}

	public function deletemb($mb) {
		$this->db->Execute('DELETE FROM '.$this->tablenames['imap_demo'].' WHERE mailbox='.$this->db->qstr($mb).' OR mailbox LIKE '.$this->db->qstr($mb.'%'));
		if($this->db->Affected_Rows() < 1) {
			return false;
		}
		return true;
	}

	public function renamemb($from_mb, $to_mb) {
		$this->db->Execute('UPDATE '.$this->tablenames['imap_demo']
			.' SET mailbox=REPLACE(mailbox, '.$this->db->qstr($from_mb).', '.$this->db->qstr($to_mb).'), '
				.'ACL=REPLACE(ACL, '.$this->db->qstr(str_replace('user.', '', $from_mb)).', '.$this->db->qstr(str_replace('user.', '', $to_mb)).')'
			.' WHERE mailbox = '.$this->db->qstr($from_mb).' OR mailbox LIKE '.$this->db->qstr($from_mb.'%'));
		if($this->db->ErrorMsg() != '') {
			return false;
		}
		return true;
	}

	public function getmailboxes($ref = '', $pat = '*') {
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

	public function setquota($mb, $many, $storage = '') {
		if(is_numeric($many)) {
			$this->db->Execute('UPDATE '.$this->tablenames['imap_demo'].' SET qmax='.intval(max(1, $many)).', used=FLOOR(RAND()*'.intval(max(1, $many)).') WHERE mailbox='.$this->db->qstr($mb));
		} else if(is_null($many)) {
			$this->db->Execute('UPDATE '.$this->tablenames['imap_demo'].' SET qmax=0 WHERE mailbox='.$this->db->qstr($mb));
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

	public function getquota($mb) {
		$row = $this->db->GetRow('SELECT qmax,used FROM '.$this->tablenames['imap_demo'].' WHERE mailbox='.$this->db->qstr($mb));
		if($row === false || !isset($row['qmax'])) {
			$this->error_msg	= 'Given mailbox does not exist.';
			return new Quota();
		} else {
			if($row['qmax'] == 0) {
				return new Quota();
			}
			return new Quota($row['used'], $row['qmax']);
		}
	}

	public function get_users_quota($username) {
		return $this->getquota($this->format_user($username));
	}

	public function get_acl_available() {
		$assumed = array('l', 'r', 's', 'w', 'i', 'p', 'c', 'd', 'a');
		return $assumed;
	}

	public function setacl($mb, $user, $acl) {
		global $oma;

		// does the user exist?
		$user = $this->db->GetOne('SELECT mbox FROM '.$this->tablenames['user'].' WHERE mbox='.$this->db->qstr($user));
		if(!$user === false) {
			// fetch old ACL
			$facl = $this->getacl($mb);
			if(isset($facl[$oma->current_user->mbox]) && stristr($facl[$oma->current_user->mbox], 'a')
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
				$this->db->Execute('UPDATE '.$this->tablenames['imap_demo'].' SET ACL='.$this->db->qstr(trim($store)).' WHERE mailbox='.$this->db->qstr($mb));
				return true;
			}
		} else {
			$this->error_msg	= 'User does not exist.';
		}
		return false;
	}

	public function getacl($mb) {
		$acl = $this->db->GetOne('SELECT ACL FROM '.$this->tablenames['imap_demo'].' WHERE mailbox='.$this->db->qstr($mb));
		if($acl === false) {
			return array();
		} else {
			return hsys_getACLInfo(array('* ACL '.$mb.' '.$acl), $mb);
		}
	}

	public function getversion() {
		return '2.2.12';
	}

	public function format_user($username, $folder = null) {
		if(is_null($folder)) {
			return('user'.$this->gethierarchyseparator().$username.$this->connection_data['VDOM']);
		} else {
			return($this->format_user($username).$this->gethierarchyseparator().$folder);
		}
	}

}
?>