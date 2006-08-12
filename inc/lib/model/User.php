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
	 * @return	Array
	 */
	public static function get_descendants_IDs(User $parent, $levels = 7) {
		// unfortunately, we will have to emulate hierarchical queries
		$sql = 'SELECT DISTINCT lvl'.$levels.'.ID FROM '.self::$tablenames['user'].' lvl1';
		for($l = 2; $l <= $levels; $l++) {
			$sql .= ' LEFT JOIN oma1_user lvl'.$l.' ON ( lvl'.($l - 1).'.ID = lvl'.$l.'.pate )';
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

	/**
	 * @param	data	Array with all available data about this particular user.
	 */
	protected function __construct($data) {
		global $cfg;
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
	 * @deprecated 			Will be removed after native virtual domain support has been implemented.
	 * @throws	Exception	if user does not exist.
	 */
	public static function get_by_name($username) {
		$id = self::$db->GetOne('SELECT ID FROM '.self::$tablenames['user'].' WHERE mbox='.self::$db->qstr($username));
		return self::get_by_ID($id);
	}

	public static function get_by_ID($id) {
		static $cache	= array();
		if(!isset($cache[$id])) {
			$cache[$id] = self::get_immediate_by_ID($id);
		}
		return $cache[$id];
	}

	/**
	 * @throws	Exception	if user does not exist.
	 */
	private static function get_immediate_by_ID($id) {
		$data = self::$db->GetRow('SELECT * FROM '.self::$tablenames['user'].' WHERE ID='.self::$db->qstr($id));
		if($data === false) {
			throw new Exception(txt(2));
		}
		return new User($data);
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
		$usr	= self::get_by_name($username);
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