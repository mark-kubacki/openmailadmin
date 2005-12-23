<?php
/*
 * This class encapsulates the administrative backend of cyrus-imapd.
 * It does not rely on PHP's IMAP/POP classes and connects on demand.
 *
 * Relevant RFCs:
 * - mailboxes:		3501
 * - quota:		2047
 * - ACL:		2086
 */

class imapd_adm {

	// private:
	var $sp = false;	// holds the socket ressource, if connected
	var $connection_data;	// everything neccessary to connect to cyrus
	var $version;		// version of cyrus-imapd we have connected to

	// public:
	var $error_msg;		// if an error occured, this variable will hold the error messages
	var $separator;		// hierarchy separator

	/*
	* Constructor. Takes login data as arguments.
	*/
	function imapd_adm($connection_data) {
		$this->version		= 'unknown';
		$this->separator	= '.';
		$this->connection_data	= $connection_data;
	}

	/***** connection ****/

	function imap_login() {
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

	function imap_logout() {
		if($this->sp) {
			$this->command('. logout');
			fclose($this->sp);
		}
		return true;
	}

	function getversion() {
		if(!$this->sp) {
			$this->imap_login();
		}
		return $this->version;
	}

	function gethierarchyseparator() {
		$result = $this->command('. list "" ""');
		$tmp = strstr($result['0'], '"');
		$this->separator = $tmp{1};
		return $this->separator;
	}

	/*
	* Sends commands to cyrus and returns the response.
	* Unless additional data is provided the return will be either true
	* or false. On additional data an array will be returned.
	*/
	function command($cmd) {
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

	/***** mailbox manipulation *****/

	function createmb($mailboxname) {
		return $this->command('. create "'.$mailboxname.'"');
	}

	function deletemb($mailboxname) {
		// we have to grant ourselve admin-rights on the mailbox before deleting it
		$this->setacl($mailboxname, $this->connection_data['ADMIN'], 'lrswipcda');
		return $this->command('. delete "'.$mailboxname.'"');
	}

	function renamemb($oldname, $newname) {
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

	/*
	* Returns an array with these attributes:
	* name, delimiter, attributes (don't rely on the latter)
	*/
	function getmailboxes($ref = '', $pat = '*') {
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

	/***** quota manipulation *****/

	/*
	* Returns usage as 'used' and quota 'qmax'.
	* If quota is unlimited or not set both values are 'NOT-SET'.
	*/
	function getquota($mailboxname) {
		$ret = array();
		$out = $this->command('. getquota "'.$mailboxname.'"');

		if($out == false) {
			// quota not set, thus unlimited
			$ret = array('used' => 'NOT-SET', 'qmax' => 'NOT-SET');
		} else if(is_array($out)
			   && preg_match('/\*\sQUOTA.*\(\w*\s(\d+)\s(\d+)\)/i', $out[0], $arr)) {
			$ret = array('used' => $arr[1], 'qmax' => $arr[2]);
		}

		return $ret;
	}

	/*
	* Sets storage limitations on a given mailbox.
	* Quota has to be an integer, its dimension is kib. If quota is left out
	* or null the mailbox' quota will be removed and thus regarded as
	* 'not set' - that means unlimited.
	*/
	function setquota($mailboxname, $quota = null, $storage = 'STORAGE') {
		$data = '';
		if(is_null($quota)) {
			$data = '()';
		} else if(is_numeric($quota)) {
			$data = '('.$storage.' '.intval($quota).')';
		}
		return $this->command('. setquota "'.$mailboxname.'" '.$data);
	}

	/***** ACL management *****/

	function getacl($mailboxname) {
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

	function setacl($mailboxname, $user, $ACL) {
		return $this->command('. setacl "'.$mailboxname.'" "'.$user.'" '.$ACL);
	}

	function deleteacl($mailboxname, $user) {
		return $this->command('. deleteacl "'.$mailboxname.'" "'.$user.'"');
	}

	/**
	 * Adds prefixes and suffixes as well as separators to a username
	 */
	function format_user($username, $folder = null) {
		if(is_null($folder)) {
			return('user'.$this->separator.$username.$this->connection_data['VDOM']);
		} else {
			return($this->format_user($username).$this->separator.$folder);
		}
	}

}
?>