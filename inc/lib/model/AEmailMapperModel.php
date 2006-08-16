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

	protected function immediate_set($attribute, $value, $tablename) {
		return parent::immediate_set($attribute, $value, $tablename, 'ID', array('dest'));
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

}
?>