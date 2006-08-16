<?php
class AddressesController
	extends AEmailMapperController
	implements INavigationContributor
{
	public function get_navigation_items() {
		if($this->oma->current_user->max_alias > 0 || $this->oma->authenticated_user->a_super >= 1 || $this->oma->current_user->get_used_alias()) {
			return array('link'		=> 'addresses.php'.($this->oma->current_user != $this->oma->authenticated_user ? '?cuser='.$this->oma->current_user->ID : ''),
					'caption'	=> txt('17'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'addresses.php'));
		}
		return false;
	}

	public function controller_get_shortname() {
		return 'address';
	}

	/**
	 * @return	Array		with instances as values and IDs as keys.
	 */
	private function get_manipulable(User $owner, array $ids) {
		$addr = Address::get_by_owner($owner);
		$list = array();
		foreach($ids as $id) {
			if(isset($addr[$id])) {
				$list[$id] = $addr[$id];
			}
		}
		return $list;
	}

	public function get_list() {
		$addresses = Address::get_by_owner($this->oma->current_user, $_SESSION['limit'], $_SESSION['offset']['address']);
		$list = array();
		foreach($addresses as $addr) {
			$row = array();
			$row['ID'] = $addr->ID;
			$row['active'] = $addr->active;
			if($addr->alias == '')
				$row['alias'] = '*';
			else
				$row['alias'] = $addr->alias;
			$row['domain'] = $addr->get_domain()->domain;
			$row['dest'] = $addr->get_destinations();
			$list[] = $row;
		}
		return $list;
	}

	public function create($alias, $domain_ID, array $destinations) {
		// May the user create another address?
		if($this->oma->current_user->get_used_alias() < $this->oma->current_user->max_alias
		   || $this->oma->authenticated_user->a_super >= 1) {
			// May he use the given domain?
			if(! in_array($domain_ID,
					array_keys($this->oma->domain->get_usable_by_user($this->oma->current_user)))) {
				$this->ErrorHandler->add_error(txt('16'));
				return false;
			}
			$domain = Domain::get_by_ID($domain_ID);
			// If he did choose a catchall, may he create such an address?
			if($alias == '*' && $this->oma->cfg['address']['allow_catchall']) {
				if($this->oma->cfg['address']['restrict_catchall']) {
					if(! ($domain->get_owner() == $this->oma->current_user
					      || in_array($this->oma->current_user, $domain->get_administrators())) ) {
						$this->ErrorHandler->add_error(txt('16'));
						return false;
					}
					// There shall be no local part in the address. That is characteristic for catchalls.
					$alias = '';
				}
			}
			// Will his new address be a valid one?
			else if(! preg_match('/([A-Z0-9\.\-\_]{'.strlen($alias).'})/i', $alias)) {
				$this->ErrorHandler->add_error(txt('13'));
				return false;
			}
			try {
				$ret = Address::create($alias, $domain, $this->oma->current_user, $destinations);
				$this->ErrorHandler->add_info(sprintf(txt('135'), $ret->__toString()));
				return true;
			} catch(DuplicateEntryException $e) {
				$this->ErrorHandler->add_error(txt('133'));
			}
		} else {
			$this->ErrorHandler->add_error(txt('14'));
		}
		return false;
	}

	public function delete($arr_IDs) {
		$res = true;
		$deleted = array();
		foreach($this->get_manipulable($this->oma->current_user, $arr_IDs) as $id => $addr) {
			$t = Address::delete_by_ID($id);
			if($res && !$t
			   && $this->oma->db->ErrorNo() != 0) {
				$this->ErrorHandler->add_error($this->oma->db->ErrorMsg());
			} else {
				$deleted[] = $addr->__toString();
			}
			$res = $res && $t;
		}
		if(count($deleted) > 0) {
			$this->ErrorHandler->add_info(sprintf(txt(15), '<ul><li><cite>'.implode('</cite></li><li><cite>', $deleted).'</cite></li></ul>'));
		}
		return count($deleted) > 0 && $res;
	}

	public function change_destination($arr_IDs, $arr_destinations) {
		foreach($this->get_manipulable($this->oma->current_user, $arr_IDs) as $addr) {
			$addr->set_destinations($arr_destinations);
		}
	}

	public function toggle_active($arr_IDs) {
		foreach($this->get_manipulable($this->oma->current_user, $arr_IDs) as $addr) {
			$addr->set_active(!$addr->active);
		}
	}

}
?>