<?php
class Address
	extends AEmailMapperModel
{
	public function set_destinations(array $destinations) {
		return parent::set_destinations($destinations, self::$tablenames['virtual']);
	}

	public static function get_by_ID($id) {
		static $cache	= array();
		if(!isset($cache[$id])) {
			$cache[$id] = self::get_immediate_by_ID($id);
		}
		return $cache[$id];
	}

	public static function delete_by_ID($id) {
		return parent::delete_by_ID($id, self::$tablenames['virtual']);
	}

	/**
	 * @return	Address
	 */
	public static function create($alias, Domain $domain, User $owner, array $destinations) {
		$res = self::$db->Execute('INSERT INTO '.self::$tablenames['virtual']
				.' (owner,alias,domain,dest,active) VALUES (?,?,?,?,?)',
				array($owner->ID, $alias, $domain->ID, implode(',', $destinations), 1));
		$id = self::$db->Insert_ID();
		return self::get_by_ID($id);
	}

	protected static function get_immediate_by_ID($id) {
		return parent::get_immediate_by_ID($id, self::$tablenames['virtual'], 'Address');
	}

}
?>