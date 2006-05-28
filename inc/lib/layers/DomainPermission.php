<?php
/**
 * This layer's purpose is:
 * - deny access to objects which the user has no permission to
 * - if many objects are to be changed, only let the allowed pass through
 * - permit only allowed actions
 */
class DomainPermission
	extends DomainCoreDecorator
{
	public function add($domain, $properties) {
		return $this->inner->add($domain, $properties);
	}

	/**
	 * @param	user		Instance of User.
	 * @returns	The IDs of domains whose properties the user may change.
	 */
	private function get_modifiable_domains(User $user) {
		$domain_ids	= array();
		if(self::$cfg['admins_delete_domains']) {
			$result = self::$db->SelectLimit('SELECT ID'
				.' FROM '.self::$tablenames['domains']
				.' WHERE (owner='.self::$db->qstr($user->mbox).' OR a_admin LIKE '.self::$db->qstr('%'.$user->mbox.'%').') AND '.db_find_in_set(self::$db, 'ID', $domains),
				count($domains));
		} else {
			$result = self::$db->SelectLimit('SELECT ID'
				.' FROM '.$this->tablenames['domains']
				.' WHERE owner='.self::$db->qstr($user->mbox).' AND '.db_find_in_set(self::$db, 'ID', $domains),
				count($domains));
		}
		if(!$result === false) {
			while(!$result->EOF) {
				$domain_ids	= $result->fields['ID'];
				$result->MoveNext();
			}
		}
		return $domain_ids;
	}

	public function remove($domains) {
		$domains	= $this->get_modifiable_domains(self::$mgr->authenticated_user);
		if(count($domains) > 0) {
			return $this->inner->remove($domains);
		} else {
			self::$ErrorHandler->add_error(txt('16'));
			return false;
		}
	}

	public function change($domains, $change, $data) {
		$domains	= $this->get_modifiable_domains(self::$mgr->authenticated_user);
		if(count($domains) > 0) {
			return $this->inner->change($domains, $change, $data);
		} else {
			self::$ErrorHandler->add_error(txt('16'));
			return false;
		}
	}

}
?>