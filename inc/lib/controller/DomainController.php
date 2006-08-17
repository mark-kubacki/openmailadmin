<?php
class DomainController
	extends AOMAController
	implements INavigationContributor
{
	public function get_navigation_items() {
		if($this->oma->authenticated_user->a_admin_domains >= 1 || $this->oma->current_user->get_number_domains() > 0) {
			return array('link'		=> 'domains.php'.($this->oma->current_user != $this->oma->authenticated_user ? '?cuser='.$this->oma->current_user->ID : ''),
					'caption'	=> txt('54'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'domains.php'));
		}
		return false;
	}

	public function controller_get_shortname() {
		return 'domain';
	}

	/**
	 * @see 	Domain::get_usable_by_user
	 * @return	Array		with all domains the user may choose from as stringified values and their IDs as keys.
	 */
	public function get_usable_by_user(User $user, $domain_key = null) {
		$usable = Domain::get_usable_by_user($user, $domain_key);
		$list = array();
		foreach($usable as $domain) {
			$list[$domain->ID] = $domain->__toString();
		}
		return $list;
	}

	public $editable_domains;	// How many domains can the current user change?

	public function get_list() {
		$this->editable_domains = 0;
		$ret = array();
		foreach(Domain::get_usable_by_user($this->oma->current_user) as $domain) {
			$row['ID'] = $domain->ID;
			$row['domain'] = $domain->__toString();
			$row['owner'] = is_null($domain->owner) ? txt(136) : $domain->get_owner()->__toString();
			$row['categories'] = $domain->categories;
			$adm = array();
			foreach($domain->get_administrators() as $admin) {
				$adm[] = $admin->mbox;
			}
			if(count($adm) == 0)
				$row['a_admin'] = txt(137);
			else
				$row['a_admin'] = implode(', ', $adm);
			if($this->oma->current_user->ID == $domain->owner
			   || in_array($this->oma->current_user->mbox, $adm)) {
				++$this->editable_domains;
				$row['selectable'] = true;
			} else {
				$row['selectable'] = false;
			}
			$ret[] = $row;
		}
		return $ret;
	}

	/**
	 * Use this to check whether the user "tobechecked" wuth given "domain_key"
	 * has not been granted access to more/other domains the user "reference"
	 * already has.
	 *
	 * @param	reference	Instance of User
	 * @param	tobechecked	Mailbox-name.
	 * @return	Boolean
	 */
	public function only_subset_available(User $reference, User $tobechecked, $domain_key) {
		$domains_available_to_reference = $this->get_usable_by_user($reference);
		// new domain-key must not lead to more domains than the user already has to choose from
		// A = Domains the new user will be able to choose from.
		$dom_a = $this->get_usable_by_user($tobechecked, $domain_key);
		// B = Domains the creator may choose from (that is $domains_available_to_reference)?
		// Okay, if A is part of B. (Thus, no additional domains are added for user "A".)
		// Indication: A <= B
		if(count($dom_a) == 0) {
			// This will be only a warning.
			$this->ErrorHandler->add_error(txt('80'));
		} else if(count($dom_a) > count($domains_available_to_reference)
			   && count(array_diff($dom_a, $domains_available_to_reference)) > 0) {
			// A could have domains which the reference cannot access.
			return false;
		}

		return true;
	}
	/*
	 * Adds a new domain into the corresponding table.
	 * Categories are for grouping domains.
	 */
	public function add($domain, $props) {
		$props['domain'] = $domain;
		if(!$this->oma->validator->validate($props, array('domain', 'categories', 'owner', 'a_admin'))) {
			return false;
		}

		if(!stristr($props['categories'], 'all'))
			$props['categories'] = 'all,'.$props['categories'];
		if(!stristr($props['a_admin'], $this->oma->current_user->mbox))
			$props['a_admin'] .= ','.$this->oma->current_user->mbox;

		$this->oma->db->Execute('INSERT INTO '.$this->oma->tablenames['domains'].' (domain, categories, owner, a_admin) VALUES (?, ?, ?, ?)',
				array($domain, $props['categories'], $props['owner'], $props['a_admin']));
		if($this->oma->db->Affected_Rows() < 1) {
			$this->ErrorHandler->add_error(txt('134'));
		} else {
			return true;
		}

		return false;
	}
	/*
	 * Not only removes the given domains by their ids,
	 * it deactivates every address which ends in that domain.
	 */
	public function remove($domains) {
		// We need the old domain name later...
		if(is_array($domains) && count($domains) > 0) {
			if($this->oma->cfg['admins_delete_domains']) {
				$result = $this->oma->db->SelectLimit('SELECT ID, domain'
					.' FROM '.$this->oma->tablenames['domains']
					.' WHERE (owner='.$this->oma->db->qstr($this->oma->authenticated_user->ID).' OR a_admin LIKE '.$this->oma->db->qstr('%'.$this->oma->authenticated_user->mbox.'%').') AND '.db_find_in_set($this->oma->db, 'ID', $domains),
					count($domains));
			} else {
				$result = $this->oma->db->SelectLimit('SELECT ID, domain'
					.' FROM '.$this->oma->tablenames['domains']
					.' WHERE owner='.$this->oma->db->qstr($this->oma->authenticated_user->ID).' AND '.db_find_in_set($this->oma->db, 'ID', $domains),
					count($domains));
			}
			if(!$result === false) {
				while(!$result->EOF) {
					$del_ID[] = $result->fields['ID'];
					$del_nm[] = $result->fields['domain'];
					$result->MoveNext();
				}
				if(isset($del_ID)) {
					$this->oma->db->Execute('DELETE FROM '.$this->oma->tablenames['domains'].' WHERE '.db_find_in_set($this->oma->db, 'ID', $del_ID));
					if($this->oma->db->Affected_Rows() < 1) {
						if($this->oma->db->ErrorNo() != 0) {
							$this->ErrorHandler->add_error($this->oma->db->ErrorMsg());
						}
					} else {
						$this->ErrorHandler->add_info(txt('52').'<br />'.implode(', ', $del_nm));
						// We better deactivate all aliases containing that domain, so users can see the domain was deleted.
						foreach($del_nm as $domainname) {
							$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual'].' SET active = 0 WHERE address LIKE '.$this->oma->db->qstr('%'.$domainname));
						}
						// We can't do such on REGEXP addresses: They may catch more than the given domains.
						return true;
					}
				} else {
					$this->ErrorHandler->add_error(txt('16'));
				}
			} else {
				$this->ErrorHandler->add_error(txt('16'));
			}
		} else {
			$this->ErrorHandler->add_error(txt('11'));
		}

		return false;
	}
	/*
	 * Every parameter is an array. $domains contains IDs.
	 */
	public function change($domains, $change, $data) {
		$toc = array();		// to be changed

		if(!$this->oma->validator->validate($data, $change)) {
			return false;
		}

		if(!is_array($change)) {
			$this->ErrorHandler->add_error(txt('53'));
			return false;
		}
		if($this->oma->cfg['admins_delete_domains'] && in_array('owner', $change))
			$toc[] = 'owner='.$this->oma->db->qstr($data['owner']);
		if(in_array('a_admin', $change))
			$toc[] = 'a_admin='.$this->oma->db->qstr($data['a_admin']);
		if(in_array('categories', $change))
			$toc[] = 'categories='.$this->oma->db->qstr($data['categories']);
		if(count($toc) > 0) {
			$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['domains']
				.' SET '.implode(',', $toc)
				.' WHERE (owner='.$this->oma->db->qstr($this->oma->authenticated_user->ID).' or a_admin LIKE '.$this->oma->db->qstr('%'.$this->oma->authenticated_user->mbox.'%').') AND '.db_find_in_set($this->oma->db, 'ID', $domains));
			if($this->oma->db->Affected_Rows() < 1) {
				if($this->oma->db->ErrorNo() != 0) {
					$this->ErrorHandler->add_error($this->oma->db->ErrorMsg());
				} else {
					$this->ErrorHandler->add_error(txt('16'));
				}
			}
		}
		// changing ownership if $this->oma->cfg['admins_delete_domains'] == false
		if(!$this->oma->cfg['admins_delete_domains'] && in_array('owner', $change)) {
			$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['domains']
				.' SET owner='.$this->oma->db->qstr($data['owner'])
				.' WHERE owner='.$this->oma->db->qstr($this->oma->authenticated_user->ID).' AND '.db_find_in_set($this->oma->db, 'ID', $domains));
		}
		// No domain be renamed?
		if(! in_array('domain', $change)) {
			return true;
		}
		// Otherwise (and if only one) try adapting older addresses.
		if(count($domains) == 1) {
			// Grep the old name, we will need it later for replacement.
			$domain = $this->oma->db->GetRow('SELECT ID, domain AS name FROM '.$this->oma->tablenames['domains'].' WHERE ID = '.$this->oma->db->qstr($domains[0]).' AND (owner='.$this->oma->db->qstr($this->oma->authenticated_user->ID).' or a_admin LIKE '.$this->oma->db->qstr('%'.$this->oma->authenticated_user->mbox.'%').')');
			if(!$domain === false) {
				// First, update the name. (Corresponding field is marked as unique, therefore we will not receive doublettes.)...
				$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['domains'].' SET domain = '.$this->oma->db->qstr($data['domain']).' WHERE ID = '.$domain['ID']);
				// ... and then, change every old address.
				if($this->oma->db->Affected_Rows() == 1) {
					// dest
					$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual'].' SET dest = REPLACE(dest, '.$this->oma->db->qstr('@'.$domain['name']).', '.$this->oma->db->qstr('@'.$data['domain']).') WHERE dest LIKE '.$this->oma->db->qstr('%@'.$domain['name'].'%'));
					$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual_regexp'].' SET dest = REPLACE(dest, '.$this->oma->db->qstr('@'.$domain['name']).', '.$this->oma->db->qstr('@'.$data['domain']).') WHERE dest LIKE '.$this->oma->db->qstr('%@'.$domain['name'].'%'));
				} else {
					$this->ErrorHandler->add_error($this->oma->db->ErrorMsg());
				}
				return true;
			} else {
				$this->ErrorHandler->add_error(txt('91'));
			}
		} else {
			$this->ErrorHandler->add_error(txt('53'));
		}

		return false;
	}

}
?>