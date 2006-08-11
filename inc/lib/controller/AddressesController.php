<?php
class AddressesController
	extends AOMAController
	implements INavigationContributor
{
	public function get_navigation_items() {
		$oma = $this->oma;
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
	public function get_addresses() {
		$alias = array();

		$result = $this->db->SelectLimit('SELECT v.ID, alias, d.domain, dest, active'
					.' FROM '.$this->tablenames['virtual'].' v JOIN '.$this->tablenames['domains'].' d ON (v.domain = d.ID)'
					.' WHERE v.owner='.$this->db->qstr($this->current_user->mbox).$_SESSION['filter']['str']['address']
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
					if($value == $this->current_user->mbox)
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

	/*
	 * Creates a new email-address.
	 */
	public function address_create($alias, $domain, $arr_destinations) {
		// May the user create another address?
		if($this->current_user->used_alias < $this->current_user->max_alias
		   || $this->authenticated_user->a_super >= 1) {
			// May he use the given domain?
			if(! in_array($domain, $this->get_domain_set($this->current_user->mbox, $this->current_user->domains))) {
				$this->ErrorHandler->add_error(txt('16'));
				return false;
			}
			// If he did choose a catchall, may he create such an address?
			if($alias == '*' && $this->cfg['address']['allow_catchall']) {
				if($this->cfg['address']['restrict_catchall']) {
					// If either the current or the authenticated user is
					// owner of that given domain, we can permit creation of that catchall.
					$result = $this->db->GetOne('SELECT domain FROM '.$this->tablenames['domains']
								.' WHERE domain='.$this->db->qstr($domain)
								.' AND (owner='.$this->db->qstr($this->current_user->mbox).' OR owner='.$this->db->qstr($this->authenticated_user->mbox).')');
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
			$domain_ID = $this->db->GetOne('SELECT ID FROM '.$this->tablenames['domains']
								.' WHERE domain='.$this->db->qstr($domain));
			$this->db->Execute('INSERT INTO '.$this->tablenames['virtual'].' (alias, domain, dest, owner) VALUES (?, ?, ?, ?)',
						array(strtolower($alias), $domain_ID, implode(',', $arr_destinations), $this->current_user->mbox));
			if($this->db->Affected_Rows() < 1) {
				$this->ErrorHandler->add_error(txt('133'));
			} else {
				$this->ErrorHandler->add_info(sprintf(txt('135'), strtolower($alias).'@'.$domain));
				$this->current_user->used_alias++;
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
	public function address_delete($arr_IDs) {
		$tmp
		= $this->db->GetCol('SELECT CONCAT(v.alias, '.$this->db->qstr('@').', d.domain)'
				.' FROM '.$this->tablenames['virtual'].' v JOIN '.$this->tablenames['domains'].' d ON (v.domain = d.ID)'
				.' WHERE v.owner='.$this->db->qstr($this->current_user->mbox)
				.' AND '.db_find_in_set($this->db, 'v.ID', $arr_IDs));
		$this->db->Execute('DELETE FROM '.$this->tablenames['virtual']
				.' WHERE owner='.$this->db->qstr($this->current_user->mbox)
				.' AND '.db_find_in_set($this->db, 'ID', $arr_IDs));
		if($this->db->Affected_Rows() < 1) {
			if($this->db->ErrorNo() != 0) {
				$this->ErrorHandler->add_error($this->db->ErrorMsg());
			}
		} else {
			$this->ErrorHandler->add_info(sprintf(txt('15'), implode(', ', $tmp)));
			$this->current_user->used_alias -= $this->db->Affected_Rows();
			return true;
		}

		return false;
	}
	/*
	 * Changes the destination of the given addresses if they belong to the current user.
	 */
	public function address_change_destination($arr_IDs, $arr_destinations) {
		$this->db->Execute('UPDATE '.$this->tablenames['virtual'].' SET dest='.$this->db->qstr(implode(',', $arr_destinations)).', neu=1'
				.' WHERE owner='.$this->db->qstr($this->current_user->mbox)
				.' AND '.db_find_in_set($this->db, 'address', $arr_IDs));
		if($this->db->Affected_Rows() < 1) {
			if($this->db->ErrorNo() != 0) {
				$this->ErrorHandler->add_error($this->db->ErrorMsg());
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
	public function address_toggle_active($arr_IDs) {
		$this->db->Execute('UPDATE '.$this->tablenames['virtual'].' SET active=NOT active, neu=1'
				.' WHERE owner='.$this->db->qstr($this->current_user->mbox)
				.' AND '.db_find_in_set($this->db, 'ID', $arr_IDs));
		if($this->db->Affected_Rows() < 1) {
			if($this->db->ErrorNo() != 0) {
				$this->ErrorHandler->add_error($this->db->ErrorMsg());
			}
		} else {
			return true;
		}
		return false;
	}

}
?>