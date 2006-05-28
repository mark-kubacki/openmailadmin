<?php
/**
 * This layer does actual command execution
 * and is to be called by permission and/or validation layer, first.
 */
class DomainCore
	extends ComponentLayer
	implements DomainManager
{
	public function add($domain, $props) {
		self::$db->Execute('INSERT INTO '.$this->tablenames['domains'].' (domain, categories, owner, a_admin) VALUES (?, ?, ?, ?)',
				array($domain, $props['categories'], $props['owner'], $props['a_admin']));
		if(self::$db->Affected_Rows() < 1) {
			self::$ErrorHandler->add_error(txt('134'));
			return false;
		} else {
			return true;
		}
	}

	public function remove($domains) {
		self::$db->Execute('DELETE FROM '.self::$tablenames['domains'].' WHERE '.db_find_in_set(self::$db, 'ID', $domains));
		if(self::$Affected_Rows() < 1) {
			if(self::$db->ErrorNo() != 0) {
				self::$ErrorHandler->add_error(self::$db->ErrorMsg());
			}
		} else {
			return true;
		}
	}

	private function update_property($domains, $property, $value) {
		self::$db->Execute('UPDATE '.self::$tablenames['domains']
			.' SET '.$property.'='.self::$db->qstr($value)
			.' WHERE '.db_find_in_set(self::$db, 'ID', $domains));
		if(self::$Affected_Rows() != count($domains)) {
			if(self::$db->ErrorNo() != 0) {
				self::$ErrorHandler->add_error(self::$db->ErrorMsg());
			}
		} else {
			return true;
		}
	}

	public function change($domains, $change, $data) {
		foreach($change as $property) {
			$this->update_property($domains, $property, $value);
		}
		return true;
	}

}
?>