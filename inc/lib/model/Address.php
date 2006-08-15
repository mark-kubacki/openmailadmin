<?php
class Address
	extends ATableWrapperModel
{
	public static		$db;
	public static		$tablenames;

	protected function __construct($data) {
		if(!is_array($data['dest'])) {
			$data['dest']	= self::make_dest_array($data['dest']);
		}
		parent::__construct($data);
	}

	/**
	 * Immediately set given column in database to the given value.
	 *
	 * @param	attribute	Name of attribute/SQL column to be set.
	 * @param	value		The value the field shall be assigned.
	 * @return	boolean		True if column has been changed successfully.
	 */
	protected function immediate_set($attribute, $value) {
		self::$db->Execute('UPDATE '.self::$tablenames['virtual']
				.' SET '.$attribute.'='.self::$db->qstr($value)
				.' WHERE ID='.self::$db->qstr($this->ID));
		if(!$attribute == 'dest')
			$this->{$attribute} = $value;
		if(self::$db->ErrorNo() != 0)
			throw new RuntimeException('Cannot set "'.$attribute.'" to "'.$value.'".');
		return true;
	}

	/**
	 * @return	Array
	 */
	protected static function make_dest_array($dest_string) {
		return explode(',', $dest_string);
	}

	public function set_destinations(array $destinations) {
		if($this->immediate_set('dest', implode(',', $value))) {
			$this->dest = $destinations;
			return true;
		}
		return false;
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
		return self::$db->Execute('DELETE FROM '.self::$tablenames['virtual'].' WHERE ID='.self::$db->qstr($id));
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

	/**
	 * @throws	UserNotFoundException	if user does not exist.
	 */
	private static function get_immediate_by_ID($id) {
		$data = self::$db->GetRow('SELECT * FROM '.self::$tablenames['virtual'].' WHERE ID='.self::$db->qstr($id));
		if($data === false || count($data) == 0) {
			throw new ObjectNotFoundException();
		}
		return new self($data);
	}

}
?>