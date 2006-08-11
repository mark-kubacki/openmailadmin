<?php
/**
 * Does administer all the properties, permissions and any relationship
 * methods, such as "is_descendant" etc.
 * Use instances of this class whenever data about any user is needed.
 */
class User
{
	public static		$db;
	public static		$tablenames;

	public		$password;
	private		$data		= array();

	/**
	 * @param	child		Is a username.
	 * @param	parent		Is a username.
	 * @return	Boolean
	 */
	public static function is_descendant($child, $parent, $levels = 7, $cache = array()) {
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
			$inter = self::$db->GetOne('SELECT pate FROM '.self::$tablenames['user'].' WHERE mbox='.self::$db->qstr($child));
			if($inter === false) {
				$rec = false;
			} else {
				if($inter == $parent) {
					$rec = true;
				} else if(in_array($inter, $cache)) {	// avoids loops
					$rec = false;
				} else {
					$rec = self::user_is_descendant($inter, $parent, $levels--, array_merge($cache, array($inter)));
				}
			}
		}
		$_SESSION['cache']['IsDescendant'][$parent][$child] = $rec;
		return $rec;
	}

	/**
	 * @param	username	User must exist.
	 * @throws	Exception	if user does not exist.
	 */
	public function __construct($username) {
		global $cfg;
		$data = self::$db->GetRow('SELECT * FROM '.self::$tablenames['user'].' WHERE mbox='.self::$db->qstr($username));
		if($data === false) {
			throw new Exception(txt(2));
		}
		$this->password = new Password($this, $data['password'], new $cfg['passwd']['strategy']());
		unset($data['password']);
		$this->data	= $data;
	}

	/**
	 * This is from Openmaillist's DataCarrier.
	 *
	 * @throw		If no value for $key has yet been set.
	 */
	protected function __get($key) {
		if(array_key_exists($key, $this->data)) {
			return $this->data[$key];
		} else {
			throw new Exception('Variable does not exist or has not been set.');
		}
	}

	protected function __set($key, $value) {
		if(is_null($value)) {
			if(array_key_exists($key, $this->data)) {
				unset($this->data[$key]);
			}
		} else {
			$this->data[$key] = $value;
		}
		return true;
	}

	/**
	 * Immediately set given column in database to the given value.
	 *
	 * @param	attribute	Name of attribute/SQL column to be set.
	 * @param	value		The value the field shall be assigned.
	 * @return	boolean		True if column has been changed successfully.
	 */
	public function immediate_set($attribute, $value) {
		self::$db->Execute('UPDATE '.self::$tablenames['user']
				.' SET '.$attribute.'='.self::$db->qstr($value)
				.' WHERE mbox='.self::$db->qstr($this->mbox));
		if($attribute != 'password')
			$this->{$attribute} = $value;
		if(self::$db->ErrorNo() != 0)
			throw new RuntimeException('Cannot set "'.$attribute.'" to "'.$value.'".');
		return true;
	}

	/**
	 * Use this to get a new user only if given plaintext password matches.
	 *
	 * @param	username	User must exist.
	 * @param	password	Plaintext password
	 * @return			User
	 * @throws	Exception	if user does not exist or password didn't match.
	 */
	public static function authenticate($username, $password) {
		$usr	= new User($username);
		if($usr->password->equals($password)) {
			self::$db->Execute('UPDATE '.self::$tablenames['user'].' SET last_login='.time().' WHERE mbox='.self::$db->qstr($username));
			$usr->password->store_plaintext($password);
			return $usr;
		}
		throw new Exception(txt(0));
	}

	public function is_superuser() {
		return $this->a_super >= 1;
	}

	/*
	 * How many aliases the user has already in use?
	 */
	public function get_used_alias() {
		return self::$db->GetOne('SELECT COUNT(*) FROM '.self::$tablenames['virtual'].' WHERE owner='.self::$db->qstr($this->mbox));
	}
	/*
	 * How many regexp-addresses the user has already in use?
	 */
	public function get_used_regexp() {
		return self::$db->GetOne('SELECT COUNT(*) FROM '.self::$tablenames['virtual_regexp'].' WHERE owner='.self::$db->qstr($this->mbox));
	}
	/*
	 * These just count how many elements have been assigned to that given user.
	 */
	public function get_number_mailboxes() {
		return self::$db->GetOne('SELECT COUNT(*) FROM '.self::$tablenames['user'].' WHERE pate='.self::$db->qstr($this->mbox));
	}
	/*
	 * These just count how many elements have been assigned to that given user.
	 */
	public function get_number_domains() {
		return self::$db->GetOne('SELECT COUNT(*) FROM '.self::$tablenames['domains'].' WHERE owner='.self::$db->qstr($this->mbox));
	}

}
?>