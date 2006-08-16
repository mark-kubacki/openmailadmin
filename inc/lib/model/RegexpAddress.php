<?php
class RegexpAddress
	extends AEmailMapperModel
{
	public function set_destinations(array $destinations) {
		$this->get_owner()->get_virtual_domain()->immediate_set('new_regexp', 1);
		return parent::set_destinations($destinations, self::$tablenames['virtual_regexp']);
	}

	public static function get_by_ID($id) {
		static $cache	= array();
		if(!isset($cache[$id])) {
			$cache[$id] = parent::get_immediate_by_ID($id, self::$tablenames['virtual_regexp'], 'RegexpAddress', 'ID');
		}
		return $cache[$id];
	}

	public static function delete_by_ID($id) {
		return parent::delete_by_ID($id, self::$tablenames['virtual_regexp']);
	}

	/**
	 * @return	RegexpAddress
	 */
	public static function create($regexp, User $owner, array $destinations) {
		$res = self::$db->Execute('INSERT INTO '.self::$tablenames['virtual_regexp']
				.' (owner,reg_exp,dest,active) VALUES (?,?,?,?)',
				array($owner->ID, $regexp, implode(',', $destinations), 1));
		$id = self::$db->Insert_ID();
		$owner->get_virtual_domain()->immediate_set('new_regexp', 1);
		return self::get_by_ID($id);
	}

}
?>