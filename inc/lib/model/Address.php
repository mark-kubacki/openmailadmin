<?php
class Address
	extends AEmailMapperModel
{
	public function set_active($boolean) {
		$this->get_owner()->get_virtual_domain()->immediate_set('new_emails', 1);
		return parent::immediate_set('active', ($boolean ? 1 : 0), self::$tablenames['virtual']);
	}

	public function get_domain() {
		return Domain::get_by_ID($this->domain);
	}

	public function set_destinations(array $destinations) {
		$this->get_owner()->get_virtual_domain()->immediate_set('new_emails', 1);
		return parent::set_destinations($destinations, self::$tablenames['virtual']);
	}

	public function __toString() {
		if($this->alias == '')
			$r = '*@';
		else
			$r = $this->alias.'@';
		$r .= $this->get_domain()->domain;
		return $r;
	}

	public static function get_by_ID($id) {
		static $cache	= array();
		if(!isset($cache[$id])) {
			$cache[$id] = parent::get_immediate_by_ID($id, self::$tablenames['virtual'], 'Address', 'ID');
		}
		return $cache[$id];
	}

	/**
	 * @return	Array		containing matching instances of this class with their ID as key.
	 */
	public static function get_by_owner(User $owner, $numrows = -1, $offset = -1) {
		$row = self::$db->SelectLimit('SELECT ID FROM '.self::$tablenames['virtual']
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
		return parent::delete_by_ID($id, self::$tablenames['virtual']);
	}

	/**
	 * @return	Address
	 * @throws	DuplicateEntryException
	 */
	public static function create($alias, Domain $domain, User $owner, array $destinations) {
		$res = self::$db->Execute('INSERT INTO '.self::$tablenames['virtual']
				.' (owner,alias,domain,dest,active) VALUES (?,?,?,?,?)',
				array($owner->ID, $alias, $domain->ID, implode(',', $destinations), 1));
		$id = self::$db->Insert_ID();
		if($id === false || $id == 0)
			throw new DuplicateEntryException();
		$addr = self::get_by_ID($id);
		$addr->set_destinations($destinations);
		return $addr;
	}

}
?>