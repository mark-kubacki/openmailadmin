<?php
abstract class AEmailMapperModel
	extends ATableWrapperModel
{
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
	protected function immediate_set($attribute, $value, $tablename) {
		self::$db->Execute('UPDATE '.$tablename
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

	protected function set_destinations(array $destinations, $tablename) {
		if($this->immediate_set('dest', implode(',', $destinations), $tablename)) {
			$this->dest = $destinations;
			return true;
		}
		return false;
	}

	/**
	 * @throws	InvalidArgumentException
	 */
	protected static function delete_by_ID($id, $tablename) {
		if(!is_numeric($id)) {
			throw new InvalidArgumentException();
		}
		return self::$db->Execute('DELETE FROM '.$tablename.' WHERE ID='.self::$db->qstr($id));
	}

	/**
	 * @throws	InvalidArgumentException
	 * @throws	ObjectNotFoundException	if user does not exist.
	 */
	protected static function get_immediate_by_ID($id, $tablename, $class) {
		if(!is_numeric($id)) {
			throw new InvalidArgumentException();
		}
		$data = self::$db->GetRow('SELECT * FROM '.$tablename.' WHERE ID='.self::$db->qstr($id));
		if($data === false || count($data) == 0) {
			throw new ObjectNotFoundException();
		}
		return new $class($data);
	}

}
?>