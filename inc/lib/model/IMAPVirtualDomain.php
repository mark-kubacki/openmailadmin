<?php
class IMAPVirtualDomain
	extends ATableWrapperModel
{
	public function immediate_set($attribute, $value) {
		return parent::immediate_set($attribute, $value, self::$tablenames['vdomains'], 'vdom');
	}

	public static function get_by_ID($id) {
		static $cache	= array();
		if(!isset($cache[$id])) {
			$cache[$id] = parent::get_immediate_by_ID($id, self::$tablenames['vdomains'], 'IMAPVirtualDomain', 'vdom');
		}
		return $cache[$id];
	}

	/**
	 * @return	IMAPVirtualDomain
	 */
	public static function create($name) {
		self::$db->Execute('INSERT INTO '.self::$tablenames['vdomains'].' (vdomain, new_emails, new_regexp, new_domains) VALUES (?,?,?,?)',
				array($name, 0, 0, 0));
		$id = self::$db->Insert_ID();
		return self::get_by_ID($id);
	}

	private function get_admin_IDs() {
		return self::$db->GetCol('SELECT admin FROM '.self::$tablenames['vdom_admins'].' WHERE vdom = '.self::$db->qstr($this->vdom));
	}

	/**
	 * @return	Array		of User
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
		return self::$db->Execute('INSERT INTO '.self::$tablenames['vdom_admins'].' (vdom,admin) VALUES (?,?)', array($this->vdom, $admin->ID));
	}

}
?>