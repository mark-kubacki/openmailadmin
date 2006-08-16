<?php
class Domain
	extends ATableWrapperModel
{
	public function immediate_set($attribute, $value) {
		return parent::immediate_set($attribute, $value, self::$tablenames['domains'], 'ID');
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
	 * @return	Domain
	 */
	public static function create($name, User $owner, $categories = 'all') {
		self::$db->Execute('INSERT INTO '.self::$tablenames['domains'].' (domain,categories,owner) VALUES (?,?,?)',
				array($name, $categories, $owner->ID));
		$id = self::$db->Insert_ID();
		return self::get_by_ID($id);
	}

	/**
	 * @throws	ObjectNotFoundException	if user does not exist.
	 */
	private static function get_immediate_by_ID($id) {
		$data = self::$db->GetRow('SELECT * FROM '.self::$tablenames['domains'].' WHERE ID='.self::$db->qstr($id));
		if($data === false || count($data) == 0) {
			throw new ObjectNotFoundException();
		}
		return new self($data);
	}

	private function get_admin_IDs() {
		return self::$db->GetCol('SELECT admin FROM '.self::$tablenames['domain_admins'].' WHERE domain = '.self::$db->qstr($this->ID));
	}

	/**
	 * @return	Array		of users
	 */
	public function get_administrators() {
		$admins = array();
		foreach($this->get_admin_IDs() as $id) {
			try {
				$admins[] = User::get_by_ID($id);
			} catch (ObjectNotFoundException $e) {
			}
		}
		return $admins;
	}

	public function add_administrator(User $admin) {
		return self::$db->Execute('INSERT INTO '.self::$tablenames['domain_admins'].' (domain,admin) VALUES (?,?)', array($this->ID, $admin->ID));
	}

}
?>