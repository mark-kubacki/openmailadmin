<?php
class openmailadmin
{
	public	$current_user;		// What user do we edit/display currently?
	public	$authenticated_user;	// What user did log in?

	private	$db;
	private $validator;
	protected	$ErrorHandler;

	private	$tablenames;
	private	$cfg;
	public	$imap;

	const	regex_valid_email	= '[a-z0-9]{1,}[a-z0-9\.\-\_\+]*@[a-z0-9\.\-\_]{2,}\.[a-z]{2,}';
	const	regex_valid_domain	= '[a-z0-9\-\_\.]{2,}\.[a-z]{2,}';

	function __construct(ADOConnection $adodb_handler, array $tablenames, array $cfg, IMAP_Administrator $imap) {
		$this->db		= $adodb_handler;
		$this->tablenames	= $tablenames;
		$this->cfg		= $cfg;
		$this->imap		= $imap;
		$this->validator	= new InputValidatorSuite($this, $cfg);
		$this->ErrorHandler	= ErrorHandler::getInstance();
	}

	/**
	 * Auxiliary function for initialization.
	 */
	private function get_active_controller() {
		static $controller = array();
		if(count($controller) == 0) {
			foreach(array('PasswordAndDataController', 'DomainController',
					'AddressesController', 'RegexpAddressesController',
					'MailboxController', 'IMAPFolderController')
				as $c) {
				$controller[] = new $c($this);
			}
		}
		return $controller;
	}

	/**
	 * Returns an array to be used in templates for generating the main menu.
	 */
	public function get_menu() {
		$arr_navmenu = array();
		foreach($this->get_active_controller() as $c) {
			if($c instanceof INavigationContributor) {
				$e = $c->get_navigation_items();
				if(is_array($e)) {
					$arr_navmenu[] = $e;
				}
			}
		}
		return $arr_navmenu;
	}

	/*
	 * This procedure simply executes every command stored in the array.
	 */
	private function rollback($what) {
		if(is_array($what)) {
			foreach($what as $cmd) {
				eval($cmd.';');
			}
		} else {
			eval($what.';');
		}
	}

	/*
	 * Returns a long list with every active mailbox.
	 */
	private function get_mailbox_names() {
		$tmp	= array();

		$result = $this->db->Execute('SELECT mbox FROM '.$this->tablenames['user'].' WHERE active = 1');
		while(!$result->EOF) {
			if($result->fields['mbox'] != '')
				$tmp[] = $result->fields['mbox'];
			$result->MoveNext();
		}
		return $tmp;
	}

	/*
	 * As the name says, returns an array containing the entire row
	 * of the "user" table belonging to that mailbox.
	 */
	public function get_user_row($mailbox) {
		return $this->db->GetRow('SELECT * FROM '.$this->tablenames['user'].' WHERE mbox='.$this->db->qstr($mailbox));
	}

	/*
	 * Accepts a string containing possible destination for an email-address,
	 * selects valid destinations and returns them.
	 */
	public function get_valid_destinations($possible) {
		// Define what addresses we will accept.
		$pattern  = openmailadmin::regex_valid_email;
		$pattern .= '|'.$this->current_user->mbox.'|'.txt('5').'|'.strtolower(txt('5'));
		if($this->cfg['allow_mbox_as_target']) {
			$mailboxes = &$this->get_mailbox_names();
			if(count($mailboxes) > 0) {
				$pattern .= '|'.implode('|', $mailboxes);
			}
		} else if($this->cfg['allow_wcyr_as_target']) {
			$pattern .= '|[a-z]{2,}[0-9]{4}';
		}

		// Get valid destinations.
		if(preg_match_all('/'.$pattern.'/iu', $possible, $matched)) {
			if(is_array($matched[0])) {
				// Replace every occurence of 'mailbox' with the correct name.
				array_walk($matched[0],
					create_function('&$item,$index',
							'if(strtolower($item) == \''.strtolower(txt('5')).'\') $item = \''.$this->current_user->mbox.'\';'
							));
				return $matched[0];
			}
		}
		return array();
	}

	/*
	 * Returns an array containing all domains the user may choose from.
	 */
	public function get_domain_set($user, $categories, $cache = true) {
		$cat = '';
		$poss_dom = array();

		foreach(explode(',', $categories) as $value) {
			$poss_dom[] = trim($value);
			$cat .= ' OR categories LIKE '.$this->db->qstr('%'.trim($value).'%');
		}
		$dom = array();
		$result = $this->db->Execute('SELECT domain FROM '.$this->tablenames['domains']
			.' WHERE owner='.$this->db->qstr($user).' OR a_admin LIKE '.$this->db->qstr('%'.$user.'%').' OR '.db_find_in_set($this->db, 'domain', $poss_dom).$cat);
		if(!$result === false) {
			while(!$result->EOF) {
				$dom[] = $result->fields['domain'];
				$result->MoveNext();
			}
		}
		return $dom;
	}

	/*
	 * Checks whether a user is a descendant of another user.
	 * (Unfortunately, PHP does not support inline functions.)
	 */
	public function user_is_descendant($child, $parent, $levels = 7, $cache = array()) {
		// initialize cache
		if(!isset($_SESSION['cache']['IsDescendant'])) {
			$_SESSION['cache']['IsDescendant'] = array();
		}

		if(trim($child) == '' || trim($parent) == '')
			return false;
		if(isset($_SESSION['cache']['IsDescendant'][$parent][$child]))
			return $_SESSION['cache']['IsDescendant'][$parent][$child];

		if($child == $parent) {
			$rec = true;
		} else if($levels <= 0 ) {
			$rec = false;
		} else {
			$inter = $this->db->GetOne('SELECT pate FROM '.$this->tablenames['user'].' WHERE mbox='.$this->db->qstr($child));
			if($inter === false) {
				$rec = false;
			} else {
				if($inter == $parent) {
					$rec = true;
				} else if(in_array($inter, $cache)) {	// avoids loops
					$rec = false;
				} else {
					$rec = $this->user_is_descendant($inter, $parent, $levels--, array_merge($cache, array($inter)));
				}
			}
		}
		$_SESSION['cache']['IsDescendant'][$parent][$child] = $rec;
		return $rec;
	}

	/*
	 * How many aliases the user has already in use?
	 * Does cache, but not session-wide.
	 */
	public function user_get_used_alias($username) {
		return $this->db->GetOne('SELECT COUNT(*) FROM '.$this->tablenames['virtual'].' WHERE owner='.$this->db->qstr($username));
	}
	/*
	 * How many regexp-addresses the user has already in use?
	 * Does cache, but not session-wide.
	 */
	public function user_get_used_regexp($username) {
		return $this->db->GetOne('SELECT COUNT(*) FROM '.$this->tablenames['virtual_regexp'].' WHERE owner='.$this->db->qstr($username));
	}
	/*
	 * These just count how many elements have been assigned to that given user.
	 */
	public function user_get_number_mailboxes($username) {
		return $this->db->GetOne('SELECT COUNT(*) FROM '.$this->tablenames['user'].' WHERE pate='.$this->db->qstr($username));
	}
	/*
	 * These just count how many elements have been assigned to that given user.
	 */
	public function user_get_number_domains($username) {
		return $this->db->GetOne('SELECT COUNT(*) FROM '.$this->tablenames['domains'].' WHERE owner='.$this->db->qstr($username));
	}

}
?>