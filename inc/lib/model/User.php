<?php
/**
 * Does administer all the properties, permissions and any relationship
 * methods, such as "is_descendant" etc.
 * Use instances of this class whenever data about any user is needed.
 */
class User
	extends ATableWrapperModel
{
	public		$password;

	/**
	 * @return	Array
	 */
	public static function get_descendants_IDs(User $parent, $levels = 7) {
		// unfortunately, we will have to emulate hierarchical queries
		$sql = 'SELECT DISTINCT lvl'.$levels.'.ID FROM '.self::$tablenames['user'].' lvl1';
		for($l = 2; $l <= $levels; $l++) {
			$sql .= ' LEFT JOIN '.self::$tablenames['user'].' lvl'.$l.' ON ( lvl'.($l - 1).'.ID = lvl'.$l.'.pate OR lvl1.ID = lvl'.$l.'.ID )';
		}
		$sql .= ' WHERE lvl1.ID = '.$parent->ID.' AND lvl'.$levels.'.ID IS NOT NULL';
		return self::$db->GetCol($sql);
	}

	/**
	 * @return	Boolean
	 */
	public static function is_descendant(User $child, User $parent, $levels = 7) {
		if($child == $parent
		   || $child->get_pate() == $parent) {
			return true;
		} else {
			return in_array($child->ID, self::get_descendants_IDs($parent, $levels));
		}
	}

	/**
	 * @return 		User
	 */
	public function get_pate() {
		if($this->pate == $this->ID) {
			return $this;
		} else {
			return self::get_by_ID($this->pate);
		}
	}

	/**
	 * @return 	Array 	of instances of User, including the user itself.
	 * @todo 		Retrieve them with only one query.
	 */
	public function get_all_descendants() {
		$paten = array();
		foreach(self::get_descendants_IDs($this) as $id) {
			$paten[] = self::get_by_ID($id);
		}
		return $paten;
	}

	protected function __construct($data) {
		global $cfg;
		$this->password = new Password($this, $data['password'], new $cfg['passwd']['strategy']());
		unset($data['password']);
		parent::__construct($data);
	}

	public function immediate_set($attribute, $value) {
		return parent::immediate_set($attribute, $value, self::$tablenames['user'], 'ID', array('password'));
	}

	public function set_pate(User $pate) {
		return $this->immediate_set('pate', $pate->ID);
	}

	public function get_virtual_domain() {
		return IMAPVirtualDomain::get_by_ID($this->vdom);
	}

	/**
	 * @throws	InvalidArgumentException
	 */
	public static function get_by_ID($id) {
		if(!is_numeric($id)) {
			throw new InvalidArgumentException();
		}
		static $cache	= array();
		if(!isset($cache[$id])) {
			$cache[$id] = self::get_immediate_by_ID($id);
		}
		return $cache[$id];
	}

	/**
	 * @throws	InvalidArgumentException
	 */
	public static function delete_by_ID($id) {
		if(!is_numeric($id)) {
			throw new InvalidArgumentException();
		}
		return self::$db->Execute('DELETE FROM '.self::$tablenames['user'].' WHERE ID='.self::$db->qstr($id));
	}

	/**
	 * @param	imap		if set to null, user will not be created on IMAP backend and quota will not be set.
	 * @param	realname	also known as column person.
	 * @param	domains		domain categories.
	 * @param	pate		if set to null or left empty, ID 1 will be set.
	 * @param	quota		integer, unit MiB. If set to null or left empty, no quota will be set.
	 * @return	User
	 * @throws	MailboxCreationError
	 */
	public static function create(IMAP_Administrator $imap = null, IMAPVirtualDomain $virtual_domain,
					$name, $realname, $domains = '',
					User $pate = null, $quota = null) {
		$res = self::$db->Execute('INSERT INTO '.self::$tablenames['user']
				.' (mbox, vdom, person, pate, domains, created) VALUES (?,?,?,?,?,?)',
				array($name, $virtual_domain->vdom, $realname, (is_null($pate) ? 1 : $pate->ID), $domains, time()));
		$id = self::$db->Insert_ID();
		$usr = self::get_by_ID($id);
		if(!is_null($imap)) {
			if($imap->createmb($imap->format_user($usr))) {
				if(!is_null($quota)) {
					$imap->setquota($imap->format_user($usr), $quota*1024);
				}
			} else {
				self::delete_by_ID($id);
				throw new MailboxCreationError($imap->error_msg);
			}
		}
		return $usr;
	}

	/**
	 * @throws	UserNotFoundException	if user does not exist.
	 */
	private static function get_immediate_by_ID($id) {
		$data = self::$db->GetRow('SELECT * FROM '.self::$tablenames['user'].' WHERE ID='.self::$db->qstr($id));
		if($data === false || count($data) == 0) {
			throw new UserNotFoundException(txt(2));
		}
		return new self($data);
	}

	/**
	 * Use this to get a new user only if given plaintext password matches.
	 *
	 * @param	username	User must exist.
	 * @param	password	Plaintext password
	 * @return			User
	 * @throws	AuthenticationFailureException	if user does not exist or password didn't match.
	 */
	public static function authenticate($username, $password) {
		try {
			$usr	= self::get_by_ID(self::$db->GetOne('SELECT ID FROM '.self::$tablenames['user'].' WHERE mbox='.self::$db->qstr($username)));
			if($usr->password->equals($password)) {
				self::$db->Execute('UPDATE '.self::$tablenames['user'].' SET last_login='.time().' WHERE ID='.self::$db->qstr($usr->ID));
				$usr->password->store_plaintext($password);
				return $usr;
			}
		} catch (InvalidArgumentException $e) {
		}
		throw new AuthenticationFailureException(txt(0));
	}

	public function is_superuser() {
		return $this->a_super >= 1;
	}

	/*
	 * How many aliases the user has already in use?
	 */
	public function get_used_alias() {
		return self::$db->GetOne('SELECT COUNT(*) FROM '.self::$tablenames['virtual'].' WHERE owner='.self::$db->qstr($this->ID));
	}
	/*
	 * How many regexp-addresses the user has already in use?
	 */
	public function get_used_regexp() {
		return self::$db->GetOne('SELECT COUNT(*) FROM '.self::$tablenames['virtual_regexp'].' WHERE owner='.self::$db->qstr($this->ID));
	}
	/*
	 * These just count how many elements have been assigned to that given user.
	 */
	public function get_number_mailboxes() {
		return count(self::get_descendants_IDs($this)) - 1;
	}
	/*
	 * These just count how many elements have been assigned to that given user.
	 */
	public function get_number_domains() {
		return self::$db->GetOne('SELECT COUNT(*) FROM '.self::$tablenames['domains'].' WHERE owner='.self::$db->qstr($this->ID));
	}

}
?>