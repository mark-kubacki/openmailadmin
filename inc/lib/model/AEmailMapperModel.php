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

	protected static function replace_in_dest($tablename, $from, $to) {
		$arr = self::$db->GetAll('SELECT ID, dest FROM '.$tablename.' WHERE dest LIKE '.
					self::$db->qstr('%'.$from.'%'));
		foreach($arr as $k => $row) {
			self::$db->Execute('UPDATE '.$tablename
					.' SET dest='.self::$db->qstr(str_ireplace($from, $to, $row['dest']))
					.' WHERE ID='.self::$db->qstr($row['ID']));
		}
	}

	protected function set_destinations(array $destinations, $tablename) {
		$destinations = array_unique($destinations);
		if($this->immediate_set('dest', implode(',', $destinations), $tablename)) {
			$this->dest = $destinations;
			return true;
		}
		return false;
	}

	/**
	 * @return 	Array 		with destinations as text, with the ownername replaced my 'mailbox'.
	 */
	public function get_destinations() {
		$ret = array();
		foreach($this->dest as $dest) {
			if($dest == $this->get_owner()->mbox)
				$ret[] = txt('5');
			else
				$ret[] = trim($dest);
		}
		sort($ret);
		return $ret;
	}

	/**
	 * @return	User
	 */
	public function get_owner() {
		return User::get_by_ID($this->owner);
	}

}
?>