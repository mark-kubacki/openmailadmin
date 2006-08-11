<?php
class AddressesController
	extends AEmailMapperController
	implements INavigationContributor
{
	public function get_navigation_items() {
		if($this->oma->current_user->max_alias > 0 || $this->oma->authenticated_user->a_super >= 1 || $this->oma->user_get_used_alias($this->oma->current_user->mbox)) {
			return array('link'		=> 'addresses.php'.($this->oma->current_user->mbox != $this->oma->authenticated_user->mbox ? '?cuser='.$this->oma->current_user->mbox : ''),
					'caption'	=> txt('17'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'addresses.php'));
		}
		return false;
	}

	public function controller_get_shortname() {
		return 'address';
	}

	/*
	 * Returns a long list with all addresses (the virtuals' table).
	 */
	public function get_list() {
		$alias = array();

		$result = $this->oma->db->SelectLimit('SELECT v.ID, alias, d.domain, dest, active'
					.' FROM '.$this->oma->tablenames['virtual'].' v JOIN '.$this->oma->tablenames['domains'].' d ON (v.domain = d.ID)'
					.' WHERE v.owner='.$this->oma->db->qstr($this->oma->current_user->mbox).$_SESSION['filter']['str']['address']
					.' ORDER BY domain, alias, dest',
					$_SESSION['limit'], $_SESSION['offset']['address']);
		if(!$result === false) {
			while(!$result->EOF) {
				$row	= $result->fields;
				// explode all destinations (as there may be many)
				$dest = array();
				foreach(explode(',', $row['dest']) as $value) {
					$value = trim($value);
					// replace the current user's name with "mailbox"
					if($value == $this->oma->current_user->mbox)
						$dest[] = txt('5');
					else
						$dest[] = $value;
				}
				sort($dest);
				$row['dest'] = $dest;
				if($row['alias'] == '')
					$row['alias'] = '*';
				// add the current entry to our list of aliases
				$alias[] = $row;
				$result->MoveNext();
			}
		}
		return $alias;
	}

	/**
	 * Creates a new email-address.
	 */
	public function create($alias, $domain, $arr_destinations) {
		// May the user create another address?
		if($this->oma->current_user->used_alias < $this->oma->current_user->max_alias
		   || $this->oma->authenticated_user->a_super >= 1) {
			// May he use the given domain?
			if(! in_array($domain, $this->oma->domain->get_usable_by_user($this->oma->current_user))) {
				$this->ErrorHandler->add_error(txt('16'));
				return false;
			}
			// If he did choose a catchall, may he create such an address?
			if($alias == '*' && $this->oma->cfg['address']['allow_catchall']) {
				if($this->oma->cfg['address']['restrict_catchall']) {
					// If either the current or the authenticated user is
					// owner of that given domain, we can permit creation of that catchall.
					$result = $this->oma->db->GetOne('SELECT domain FROM '.$this->oma->tablenames['domains']
								.' WHERE domain='.$this->oma->db->qstr($domain)
								.' AND (owner='.$this->oma->db->qstr($this->oma->current_user->mbox).' OR owner='.$this->oma->db->qstr($this->oma->authenticated_user->mbox).')');
					if($result === false) {			// negative check!
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
			// Finally, create that address.
			// ... get the domains ID
			$domain_ID = $this->oma->db->GetOne('SELECT ID FROM '.$this->oma->tablenames['domains']
								.' WHERE domain='.$this->oma->db->qstr($domain));
			$this->oma->db->Execute('INSERT INTO '.$this->oma->tablenames['virtual'].' (alias, domain, dest, owner) VALUES (?, ?, ?, ?)',
						array(strtolower($alias), $domain_ID, implode(',', $arr_destinations), $this->oma->current_user->mbox));
			if($this->oma->db->Affected_Rows() < 1) {
				$this->ErrorHandler->add_error(txt('133'));
			} else {
				$this->ErrorHandler->add_info(sprintf(txt('135'), strtolower($alias).'@'.$domain));
				$this->oma->current_user->used_alias++;
				return true;
			}
		} else {
			$this->ErrorHandler->add_error(txt('14'));
		}
		return false;
	}
	/**
	 * Deletes the given addresses if they belong to the current user.
	 *
	 * @param	arr_addresses		Array with IDs of the addresses to be deleted.
	 */
	public function delete($arr_IDs) {
		$tmp
		= $this->oma->db->GetCol('SELECT CONCAT(v.alias, '.$this->oma->db->qstr('@').', d.domain)'
				.' FROM '.$this->oma->tablenames['virtual'].' v JOIN '.$this->oma->tablenames['domains'].' d ON (v.domain = d.ID)'
				.' WHERE v.owner='.$this->oma->db->qstr($this->oma->current_user->mbox)
				.' AND '.db_find_in_set($this->oma->db, 'v.ID', $arr_IDs));
		$this->oma->db->Execute('DELETE FROM '.$this->oma->tablenames['virtual']
				.' WHERE owner='.$this->oma->db->qstr($this->oma->current_user->mbox)
				.' AND '.db_find_in_set($this->oma->db, 'ID', $arr_IDs));
		if($this->oma->db->Affected_Rows() < 1) {
			if($this->oma->db->ErrorNo() != 0) {
				$this->ErrorHandler->add_error($this->oma->db->ErrorMsg());
			}
		} else {
			$this->ErrorHandler->add_info(sprintf(txt('15'), implode(', ', $tmp)));
			$this->oma->current_user->used_alias -= $this->oma->db->Affected_Rows();
			return true;
		}

		return false;
	}
	/*
	 * Changes the destination of the given addresses if they belong to the current user.
	 */
	public function change_destination($arr_IDs, $arr_destinations) {
		$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual'].' SET dest='.$this->oma->db->qstr(implode(',', $arr_destinations)).', neu=1'
				.' WHERE owner='.$this->oma->db->qstr($this->oma->current_user->mbox)
				.' AND '.db_find_in_set($this->oma->db, 'address', $arr_IDs));
		if($this->oma->db->Affected_Rows() < 1) {
			if($this->oma->db->ErrorNo() != 0) {
				$this->ErrorHandler->add_error($this->oma->db->ErrorMsg());
			}
		} else {
			return true;
		}
		return false;
	}
	/*
	 * Toggles the 'active'-flag of a set of addresses  of the current user
	 * and thus sets inactive ones to active ones and vice versa.
	 */
	public function toggle_active($arr_IDs) {
		$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual'].' SET active=NOT active, neu=1'
				.' WHERE owner='.$this->oma->db->qstr($this->oma->current_user->mbox)
				.' AND '.db_find_in_set($this->oma->db, 'ID', $arr_IDs));
		if($this->oma->db->Affected_Rows() < 1) {
			if($this->oma->db->ErrorNo() != 0) {
				$this->ErrorHandler->add_error($this->oma->db->ErrorMsg());
			}
		} else {
			return true;
		}
		return false;
	}

}
?>