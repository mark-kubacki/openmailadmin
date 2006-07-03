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
	private	$_logger = null;	// logging facility, as defined in PEAR::Log

	public	$error_msg;		// if an error occured, this variable will hold the error messages

	function __construct(array $connection_data, Log $logger) {
		$this->version		= 'unknown';
		$this->separator	= '.';
		$this->connection_data	= $connection_data;
		$this->_logger		= $logger;
	}

	function __destruct() {
		$this->imap_logout();
	}

	private function imap_login() {
		$this->_logger->debug('I: On opening socket for IMAP connection.');
		$this->sp = fsockopen(	$this->connection_data['HOST'],
					$this->connection_data['PORT'],
					$errno, $errstr);
		$this->error_msg = $errstr;

		if(!$this->sp) {
			$this->_logger->error('I: Socket for IMAP connection couldn\'t be opened: "'.$errstr.'"');
			return false;
		}

		$txt = fgets($this->sp, 1024);
		$this->_logger->debug('S: '.$txt);
		if(preg_match('/IMAP4\sv(\d+\.\d+\.\d+)/', $txt, $arr)) {
			$this->version = $arr[1];
			$this->_logger->debug('I: Version of IMAP server is: "'.$this->version.'"');
		}

		$this->_logger->notice('C: -- on logging in with username "'.$this->connection_data['ADMIN'].'" --');
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

	public function gethierarchyseparator() {
		$result = $this->command('. list "" ""');
		$tmp = strstr($result['0'], '"');
		$this->separator = $tmp{1};
		$this->_logger->debug('I: Hierarchy separator is "'.$this->separator.'".');
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
		if(!($cmd{2} == 'l' && strstr($cmd, '. login'))) {
			$this->_logger->debug('C: '.$cmd);
		}
		do {
			$row = fgets($this->sp, 1024);
			$this->_logger->debug('S: '.$row);
			$out[] = $row;
		} while($row{0} != '.');

		if(count($out) > 1) {
			return $out;
		} else {
			if($row{2} != 'O') {
				$this->error_msg	= substr($row, 5);
			}
			return ($row{2} == 'O');
		}
	}

	public function createmb($mailboxname) {
		return $this->command('. create "'.$mailboxname.'"');
	}

	public function deletemb($mailboxname) {
		// we have to grant ourselve admin-rights on the mailbox before deleting it
		$this->setacl($mailboxname, $this->connection_data['ADMIN'], $this->get_acl_letters());
		return $this->command('. delete "'.$mailboxname.'"');
	}

	public function renamemb($oldname, $newname) {
		// This is for preserving already granted rights.
		$oldacl = $this->getacl($oldname);

		$this->setacl($oldname, $this->connection_data['ADMIN'], $this->get_acl_letters());
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
		return new Quota();
	}

	public function get_users_quota($username) {
		return $this->getquota($this->format_user($username));
	}

	/**
	 * @param	storage	Partition on which quota has to be set. May be ignored.
	 * @see		IMAP_Administrator::setquota
	 */
	public function setquota($mailboxname, $quota, $storage = 'STORAGE') {
		if(is_numeric($quota)) {
			return $this->command('. setquota "'.$mailboxname.'" ('.$storage.' '.intval($quota).')');
		}
		return false;
	}

	/**
	 * @returns		Array	with all available rights as letters.
	 */
	public function get_acl_available() {
		$assumed = array('l', 'r', 's', 'w', 'i', 'p', 'c', 'd', 'a');
		if(version_compare($this->getversion(), '2.3.0', '>=')) {
			$assumed = array('l', 'r', 's', 'w', 'i', 'p', 'k', 'x', 't', 'e', 'c', 'd', 'a');
		}
		return $assumed;
	}

	/**
	 * @returns		String	with all available letters which represent rights.
	 */
	private function get_acl_letters() {
		return implode('', $this->get_acl_available());
	}

	public function getacl($mailboxname) {
		$reult	= array();
		$arr	= array();
		$out = $this->command('. getacl "'.$mailboxname.'"');

		if($out === false) {
			return array();
		}

		// In order to prevent confusion due to mailboxnames which may
		// look like ACL strings we have to eliminate the mailboxnames.
		$out = str_replace($mailboxname, '##', $out);

		if(preg_match('/\*\sACL\s[^\s]*\s(.*)/', $out[0], $arr)) {
			if(preg_match_all('/([^\s]*)\s(['.$this->get_acl_letters().']*)\s?/', $arr[1], $arr)) {
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
		$ret = '';
		$this->gethierarchyseparator();
		if(is_null($folder)) {
			if(isset($this->connection_data['VDOM']) && $this->connection_data['VDOM'] != '') {
				$ret = $this->connection_data['VDOM'].'!user'.$this->separator.$username;
			} else {
				$ret = 'user'.$this->separator.$username;
			}
		} else {
			$ret = $this->format_user($username).$this->separator.$folder;
		}
		$this->_logger->notice('I: ("'.$username.'", '.(is_null($folder) ? 'null' : '"'.$folder.'"').') has been formatted as "'.$ret.'"');
		return $ret;
	}

}
?>