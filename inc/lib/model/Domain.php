<?php
class Domain
	extends ADomainModel
{
	const	regex_valid_domain	= '/^[a-z0-9\-\_\.]{2,}\.[a-z]{2,}$/i';

	public function immediate_set($attribute, $value) {
		try {
			return parent::immediate_set($attribute, $value, self::$tablenames['domains'], 'ID');
		} catch(DataException $e) {
			if($attribute == 'domain')
				throw new DuplicateEntryException();
			else
				throw $e;
		}
	}

	public function set_categories($categories) {
		if(!is_array($categories)) {
			$categories = explode(',', $categories);
		}
		$categories = implode(',', array_map('trim', $categories));
		return $this->immediate_set('categories', $categories);
	}

	public function set_owner(User $owner) {
		return parent::immediate_set('owner', $owner->ID, self::$tablenames['domains'], 'ID');
	}

	/**
	 * @return	User
	 */
	public function get_owner() {
		return User::get_by_ID($this->owner);
	}

	/**
	 * @return	Array 		of User
	 */
	public function get_administrators() {
		$adm = array();
		foreach($this->get_admin_IDs() as $id) {
			$tmp = User::get_by_ID($id);
			$adm[$tmp->ID] = $tmp;
		}
		return $adm;
	}

	public function __toString() {
		return $this->domain;
	}

	public static function get_by_ID($id) {
		static $cache	= array();
		if(!isset($cache[$id])) {
			$cache[$id] = parent::get_immediate_by_ID($id, self::$tablenames['domains'], 'Domain', 'ID');
		}
		return $cache[$id];
	}

	public static function delete_by_ID($id) {
		return parent::delete_by_ID($id, self::$tablenames['domains']);
	}

	/**
	 * @return	Array		containing matching instances of this class with their ID as key.
	 */
	public static function get_by_owner(User $owner, $numrows = -1, $offset = -1) {
		$row = self::$db->SelectLimit('SELECT DISTINCT ID FROM '.self::$tablenames['domains']
					.' WHERE owner='.self::$db->qstr($owner->ID)
					.' ORDER BY domain',
				$_SESSION['limit'], $_SESSION['offset']['regexp']);
		$res	= array();
		foreach($row as $k => $f) {
			$res[$f['ID']] = self::get_by_ID($f['ID']);
		}
		return $res;
	}

	/**
	 * @return	Array		containing matching instances of this class with their ID as key.
	 */
	public static function get_by_administrator(User $admin) {
		$ids = self::$db->GetCol('SELECT DISTINCT ID FROM '.self::$tablenames['domains'].' d JOIN '.self::$tablenames['domain_admins'].' da ON (d.ID = da.domain)'
					.' WHERE admin='.self::$db->qstr($admin->ID)
					.' ORDER BY d.domain');
		$res	= array();
		foreach($ids as $id) {
			$res[$id] = self::get_by_ID($id);
		}
		return $res;
	}

	/**
	 * @return	Array		containing matching instances of this class with their ID as key.
	 */
	public static function get_by_categories($categories) {
		if(in_array('all', explode(',', $categories))) {
			$ids = self::$db->GetCol('SELECT ID FROM '.self::$tablenames['domains'].' ORDER BY domain');
		} else {
			$poss_dom = array();
			$cat = '';
			foreach(explode(',', $categories) as $value) {
				$poss_dom[] = trim($value);
				$cat .= ' OR categories LIKE '.self::$db->qstr('%'.trim($value).'%');
			}
			$ids = self::$db->GetCol('SELECT DISTINCT ID FROM '.self::$tablenames['domains']
				.' WHERE '.db_find_in_set(self::$db, 'domain', $poss_dom).$cat
				.' ORDER BY domain');
		}
		$res	= array();
		foreach($ids as $id) {
			$res[$id] = self::get_by_ID($id);
		}
		return $res;
	}

	/**
	 * @param	domain_key	If set, the user's own domain_key is ignored and this is taken instead.
	 * @return	Array		with all domains the user may choose from as values and their IDs as keys.
	 */
	public static function get_usable_by_user(User $user, $domain_key = null) {
		$by_owner = self::get_by_owner($user);
		$by_admin = self::get_by_administrator($user);
		$by_cat = self::get_by_categories(is_null($domain_key) ? $user->domains : $domain_key);
		return $by_owner + $by_admin + $by_cat;
	}

	/**
	 * @return	Domain
	 * @throws	DuplicateEntryException
	 */
	public static function create($name, User $owner, $categories = 'all') {
		self::$db->Execute('INSERT INTO '.self::$tablenames['domains'].' (domain,categories,owner) VALUES (?,?,?)',
				array($name, $categories, $owner->ID));
		$id = self::$db->Insert_ID();
		if($id === false || $id == 0)
			throw new DuplicateEntryException();
		$owner->get_virtual_domain()->immediate_set('new_domains', 1);
		return self::get_by_ID($id);
	}

	private function get_admin_IDs() {
		return self::$db->GetCol('SELECT admin FROM '.self::$tablenames['domain_admins'].' WHERE domain = '.self::$db->qstr($this->ID));
	}

	public function purge_admin_list() {
		return self::$db->Execute('DELETE FROM '.self::$tablenames['domain_admins'].' WHERE domain='.self::$db->qstr($this->ID));
	}

	public function add_administrator(User $admin) {
		return self::$db->Execute('INSERT INTO '.self::$tablenames['domain_admins'].' (domain,admin) VALUES (?,?)', array($this->ID, $admin->ID));
	}

}
?>