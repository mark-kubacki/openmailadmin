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
	 */
	protected function immediate_set($attribute, $value, $tablename, $key = 'ID', $exceptions = array()) {
		self::$db->Execute('UPDATE '.$tablename
				.' SET '.$attribute.'='.self::$db->qstr($value)
				.' WHERE '.$key.'='.self::$db->qstr($this->{$key}));
		if(!in_array($attribute, $exceptions)) {
			$this->{$attribute} = $value;
		}
		if(self::$db->ErrorNo() != 0)
			throw new RuntimeException('Cannot set "'.$attribute.'" to "'.$value.'".');
		return true;
	}

}
?>