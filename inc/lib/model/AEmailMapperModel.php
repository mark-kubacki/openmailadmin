<?php
abstract class AEmailMapperModel
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

}
?>