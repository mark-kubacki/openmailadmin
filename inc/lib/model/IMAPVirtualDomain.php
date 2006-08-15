<?php
class IMAPVirtualDomain
{
	public static		$db;
	public static		$tablenames;

	private		$data		= array();

	/**
	 * @param	data	Array with all available data.
	 */
	protected function __construct($data) {
		$this->data	= $data;
	}

	/**
	 * This is from Openmaillist's DataCarrier.
	 *
	 * @throw		If no value for $key has yet been set.
	 */
	protected function __get($key) {
		if(array_key_exists($key, $this->data)) {
			return $this->data[$key];
		} else {
			throw new Exception('Variable does not exist or has not been set.');
		}
	}

	protected function __set($key, $value) {
		if(is_null($value)) {
			if(array_key_exists($key, $this->data)) {
				unset($this->data[$key]);
			}
		} else {
			$this->data[$key] = $value;
		}
		return true;
	}

	/**
	 * Immediately set given column in database to the given value.
	 *
	 * @param	attribute	Name of attribute/SQL column to be set.
	 * @param	value		The value the field shall be assigned.
	 * @return	boolean		True if column has been changed successfully.
	 */
	public function immediate_set($attribute, $value) {
		self::$db->Execute('UPDATE '.self::$tablenames['vdomains']
				.' SET '.$attribute.'='.self::$db->qstr($value)
				.' WHERE vdom='.self::$db->qstr($this->vdom));
		$this->{$attribute} = $value;
		if(self::$db->ErrorNo() != 0)
			throw new RuntimeException('Cannot set "'.$attribute.'" to "'.$value.'".');
		return true;
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
	 * @return	IMAPVirtualDomain
	 */
	public static function create($name) {
		self::$db->Execute('INSERT INTO '.self::$tablenames['vdomains'].' (vdomain, new_emails, new_regexp, new_domains) VALUES (?,?,?,?)',
				array($name, 0, 0, 0));
		$id = self::$db->Insert_ID();
		return self::get_by_ID($id);
	}

	/**
	 * @throws	ObjectNotFoundException
	 */
	private static function get_immediate_by_ID($id) {
		$data = self::$db->GetRow('SELECT * FROM '.self::$tablenames['vdomains'].' WHERE vdom='.self::$db->qstr($id));
		if($data === false || count($data) == 0) {
			throw new ObjectNotFoundException();
		}
		return new self($data);
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