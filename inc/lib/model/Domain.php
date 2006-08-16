<?php
class Domain
	extends ADomainModel
{
	public function immediate_set($attribute, $value) {
		return parent::immediate_set($attribute, $value, self::$tablenames['domains'], 'ID');
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

	public static function get_by_ID($id) {
		static $cache	= array();
		if(!isset($cache[$id])) {
			$cache[$id] = parent::get_immediate_by_ID($id, self::$tablenames['domains'], 'Domain', 'ID');
		}
		return $cache[$id];
	}

	/**
	 * @return	Domain
	 */
	public static function create($name, User $owner, $categories = 'all') {
		self::$db->Execute('INSERT INTO '.self::$tablenames['domains'].' (domain,categories,owner) VALUES (?,?,?)',
				array($name, $categories, $owner->ID));
		$id = self::$db->Insert_ID();
		$owner->get_virtual_domain()->immediate_set('new_domains', 1);
		return self::get_by_ID($id);
	}

	private function get_admin_IDs() {
		return self::$db->GetCol('SELECT admin FROM '.self::$tablenames['domain_admins'].' WHERE domain = '.self::$db->qstr($this->ID));
	}

	public function add_administrator(User $admin) {
		return self::$db->Execute('INSERT INTO '.self::$tablenames['domain_admins'].' (domain,admin) VALUES (?,?)', array($this->ID, $admin->ID));
	}

}
?>