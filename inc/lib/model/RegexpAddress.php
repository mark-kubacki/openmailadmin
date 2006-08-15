<?php
class RegexpAddress
	extends AEmailMapperModel
{
	public static		$db;
	public static		$tablenames;

	public function set_destinations(array $destinations) {
		return parent::set_destinations($destinations, self::$tablenames['virtual']);
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
		return self::$db->Execute('DELETE FROM '.self::$tablenames['virtual_regexp'].' WHERE ID='.self::$db->qstr($id));
	}

	/**
	 * @return	RegexpAddress
	 */
	public static function create($regexp, User $owner, array $destinations) {
		$res = self::$db->Execute('INSERT INTO '.self::$tablenames['virtual_regexp']
				.' (owner,reg_exp,dest,active) VALUES (?,?,?,?)',
				array($owner->ID, $regexp, implode(',', $destinations), 1));
		$id = self::$db->Insert_ID();
		return self::get_by_ID($id);
	}

	/**
	 * @throws	UserNotFoundException	if user does not exist.
	 */
	private static function get_immediate_by_ID($id) {
		$data = self::$db->GetRow('SELECT * FROM '.self::$tablenames['virtual_regexp'].' WHERE ID='.self::$db->qstr($id));
		if($data === false || count($data) == 0) {
			throw new ObjectNotFoundException();
		}
		return new self($data);
	}

}
?>