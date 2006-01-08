<?php
/**
 * This class encapsulates the administrative backend of cyrus-imapd.
 * It does not rely on PHP's IMAP/POP classes and connects on demand.
 *
 * Relevant RFCs:
 * - mailboxes:		3501
 * - quota:		2047
 * - ACL:		2086
 */
class Cyrus_IMAP
	implements IMAP_Administrator
{
	private	$sp = false;		// holds the socket ressource, if connected
	private	$connection_data;	// everything neccessary to connect to cyrus
	private	$version;		// version of cyrus-imapd we have connected to
	private	$separator;		// hierarchy separator

	public	$error_msg;		// if an error occured, this variable will hold the error messages

	function __construct(array $connection_data) {
		$this->version		= 'unknown';
		$this->separator	= '.';
		$this->connection_data	= $connection_data;
	}

	function __destruct() {
		$this->imap_logout();
	}

	private function imap_login() {
		$this->sp = fsockopen(	$this->connection_data['HOST'],
					$this->connection_data['PORT'],
					$errno, $errstr);
		$this->error_msg = $errstr;

		if(!$this->sp) {
			return false;
		}

		$txt = fgets($this->sp, 1024);
		if(preg_match('/IMAP4\sv(\d+\.\d+\.\d+)/', $txt, $arr)) {
			$this->version = $arr[1];
		}

		return $this->command('. login "'.$this->connection_data['ADMIN'].'" "'.$this->connection_data['PASS'].'"');
	}

	private function imap_logout() {
		if($this->sp) {
			$this->command('. logout');
			fclose($this->sp);
		}
		return true;
	}

	public function getversion() {
		if(!$this->sp) {
			$this->imap_login();
		}
		return $this->version;
	}

	private function gethierarchyseparator() {
		$result = $this->command('. list "" ""');
		$tmp = strstr($result['0'], '"');
		$this->separator = $tmp{1};
		return $this->separator;
	}

	/**
	 * @param	cmd	Fully formatted command.
	 * @return		Unless additional data is provided the return will be either true or false. On additional data an array will be returned.
	 */
	private function command($cmd) {
		if(!$this->sp && !$this->imap_login()) {
			$this->error_msg	= 'Login failed. Check your connection data.';
			return false;
		}

		$out = array();

		fputs($this->sp, $cmd."\n");
		do {
			$row = fgets($this->sp, 1024);
			$out[] = $row;
		} while($row{0} != '.');

		if(count($out) > 1) {
			return $out;
		} else {
			if($row{2} != 'O') {
				$this->error_msg	= substr($row, 6);
			}
			return ($row{2} == 'O');
		}
	}

	public function createmb($mailboxname) {
		return $this->command('. create "'.$mailboxname.'"');
	}

	public function deletemb($mailboxname) {
		// we have to grant ourselve admin-rights on the mailbox before deleting it
		$this->setacl($mailboxname, $this->connection_data['ADMIN'], 'lrswipcda');
		return $this->command('. delete "'.$mailboxname.'"');
	}

	public function renamemb($oldname, $newname) {
		// This is for preserving already granted rights.
		$oldacl = $this->getacl($oldname);

		$this->setacl($oldname, $this->connection_data['ADMIN'], 'lrswipcda');
		$out = $this->command('. rename "'.$oldname.'" "'.$newname.'"');

		if(isset($oldacl[$this->connection_data['ADMIN']])) {
			$this->setacl($newname, $this->connection_data['ADMIN'], $oldacl[$this->connection_data['ADMIN']]);
		} else {
			$this->deleteacl($newname, $this->connection_data['ADMIN']);
		}

		return $out;
	}

	public function getmailboxes($ref = '', $pat = '*') {
		$result = array();
		foreach($this->command('. list "'.$ref.'" '.$pat) as $folder) {
			if(preg_match('/\*\sLIST\s\((.*)\)\s\"(.*?)\"\s\"(.*?)\"/', $folder, $arr)) {
				$result[]
				= array('attributes'	=> $arr[1],
					'delimiter'	=> $arr[2],
					'name'		=> trim($arr[3]));
			}
		}
		return $result;
	}

	public function getquota($mailboxname) {
		$out = $this->command('. getquota "'.$mailboxname.'"');
		if($out == false) {
			// quota not set, thus unlimited
			return new Quota();
		} else if(is_array($out)
			   && preg_match('/\*\sQUOTA.*\(\w*\s(\d+)\s(\d+)\)/i', $out[0], $arr)) {
			return new Quota($arr[1], $arr[2]);
		}
		return false;
	}

	public function get_users_quota($username) {
		return $this->getquota($this->format_user($username));
	}

	/**
	 * @param	storage	Partition on which quota has to be set. May be ignored.
	 * @see		IMAP_Administrator::setquota
	 */
	public function setquota($mailboxname, $quota = null, $storage = 'STORAGE') {
		$data = '';
		if(is_null($quota)) {
			$data = '()';
		} else if(is_numeric($quota)) {
			$data = '('.$storage.' '.intval($quota).')';
		}
		return $this->command('. setquota "'.$mailboxname.'" '.$data);
	}

	public function getacl($mailboxname) {
		$reult	= array();
		$arr	= array();
		$out = $this->command('. getacl "'.$mailboxname.'"');

		// In order to prevent confusion due to mailboxnames which may
		// look like ACL strings we have to eliminate the mailboxnames.
		$out = str_replace($mailboxname, '##', $out);

		if(preg_match('/\*\sACL\s[^\s]*\s(.*)/', $out[0], $arr)) {
			if(preg_match_all('/([^\s]*)\s([lrswipcda]*)\s?/', $arr[1], $arr)) {
				$result = array_combine($arr[1], $arr[2]);
			}
		}

		return $result;
	}

	public function setacl($mailboxname, $user, $ACL) {
		return $this->command('. setacl "'.$mailboxname.'" "'.$user.'" '.$ACL);
	}

	private function deleteacl($mailboxname, $user) {
		return $this->command('. deleteacl "'.$mailboxname.'" "'.$user.'"');
	}

	public function format_user($username, $folder = null) {
		if(is_null($folder)) {
			return('user'.$this->separator.$username.$this->connection_data['VDOM']);
		} else {
			return($this->format_user($username).$this->separator.$folder);
		}
	}

}
?>