<?php
abstract class ATableWrapperModel
{
	public static	$db;
	public static	$tablenames;

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
	public function __get($key) {
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
	 * @param	exceptions	If attribute is in this list it won't we set locally.
	 * @return	boolean		True if column has been changed successfully.
	 * @throws	DataException
	 */
	protected function immediate_set($attribute, $value, $tablename, $key = 'ID', $exceptions = array()) {
		if(!in_array($attribute, $exceptions)
		   && $this->{$attribute} == $value)
			return true;
		self::$db->Execute('UPDATE '.$tablename
				.' SET '.$attribute.'='.self::$db->qstr($value)
				.' WHERE '.$key.'='.self::$db->qstr($this->{$key}));
		if(self::$db->ErrorNo() != 0)
			throw new DataException('Cannot set "'.$attribute.'" to "'.$value.'".');
		if(!in_array($attribute, $exceptions)) {
			$this->{$attribute} = $value;
		}
		return true;
	}

	/**
	 * @throws	InvalidArgumentException
	 * @throws	ObjectNotFoundException	if user does not exist.
	 */
	protected static function get_immediate_by_ID($id, $tablename, $class, $id_key) {
		if(!is_numeric($id)) {
			throw new InvalidArgumentException();
		}
		$data = self::$db->GetRow('SELECT * FROM '.$tablename.' WHERE '.$id_key.'='.self::$db->qstr($id));
		if($data === false || count($data) == 0) {
			throw new ObjectNotFoundException();
		}
		return new $class($data);
	}

	/**
	 * @throws	InvalidArgumentException
	 */
	protected static function delete_by_ID($id, $tablename, $key = 'ID') {
		if(!is_numeric($id)) {
			throw new InvalidArgumentException();
		}
		return self::$db->Execute('DELETE FROM '.$tablename.' WHERE '.$key.'='.self::$db->qstr($id));
	}

}
?>