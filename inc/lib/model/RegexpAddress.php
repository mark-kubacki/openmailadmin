<?php
class RegexpAddress
	extends AEmailMapperModel
{
	public function set_active($boolean) {
		$this->get_owner()->get_virtual_domain()->immediate_set('new_regexp', 1);
		return parent::immediate_set('active', ($boolean ? 1 : 0), self::$tablenames['virtual_regexp']);
	}

	public function set_destinations(array $destinations) {
		$this->get_owner()->get_virtual_domain()->immediate_set('new_regexp', 1);
		return parent::set_destinations($destinations, self::$tablenames['virtual_regexp']);
	}

	public function __toString() {
		return $this->reg_exp;
	}

	public static function get_by_ID($id) {
		static $cache	= array();
		if(!isset($cache[$id])) {
			$cache[$id] = parent::get_immediate_by_ID($id, self::$tablenames['virtual_regexp'], 'RegexpAddress', 'ID');
		}
		return $cache[$id];
	}

	/**
	 * @return	Array		containing matching instances of this class with their ID as key.
	 */
	public static function get_by_owner(User $owner, $numrows = -1, $offset = -1) {
		$row = self::$db->SelectLimit('SELECT ID FROM '.self::$tablenames['virtual_regexp']
					.' WHERE owner='.self::$db->qstr($owner->ID)
					.' ORDER BY dest',
				$_SESSION['limit'], $_SESSION['offset']['regexp']);
		$res	= array();
		foreach($row as $k => $f) {
			$res[$f['ID']] = self::get_by_ID($f['ID']);
		}
		return $res;
	}

	public static function delete_by_ID($id) {
		return parent::delete_by_ID($id, self::$tablenames['virtual_regexp']);
	}

	/**
	 * @return	RegexpAddress
	 * @throws	DuplicateEntryException
	 */
	public static function create($regexp, User $owner, array $destinations) {
		$res = self::$db->Execute('INSERT INTO '.self::$tablenames['virtual_regexp']
				.' (owner,reg_exp,dest,active) VALUES (?,?,?,?)',
				array($owner->ID, $regexp, '', 1));
		$id = self::$db->Insert_ID();
		if($id === false || $id == 0)
			throw new DuplicateEntryException();
		$rexp =  self::get_by_ID($id);
		$rexp->set_destinations($destinations);
		return $rexp;
	}

}
?>