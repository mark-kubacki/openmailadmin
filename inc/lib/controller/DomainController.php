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

	public function add($domain, array $props) {
		if(!$this->oma->authenticated_user->a_admin_domains > 1) {
			$this->ErrorHandler->add_error(txt(16));
			return false;
		}
		$props['domain'] = $domain;
		if(!$this->oma->validator->validate($props, array('domain', 'categories', 'owner', 'a_admin'))) {
			return false;
		}
		if(!stristr($props['categories'], 'all'))
			$props['categories'] = 'all, '.$props['categories'];
		if(!in_array($this->oma->current_user->ID, $props['a_admin']))
			$props['a_admin'][] = $this->oma->current_user->ID;
		try {
			$domain = Domain::create($props['domain'], $this->oma->current_user, $props['categories']);
			foreach($props['a_admin'] as $id) {
				try {
					$domain->add_administrator(User::get_by_ID($id));
				} catch(ObjectNotFoundException $e) {
				}
			}
			$this->ErrorHandler->add_info(txt(138));
		} catch(DuplicateEntryException $e) {
			$this->ErrorHandler->add_error(txt(134));
			return false;
		}
		return true;
	}

	/**
	 * @param	domains	as array of their IDs.
	 * @todo		Store deleted emails somewhere so users can see what they have lost.
	 */
	public function remove(array $domains) {
		$deleted = array();
		foreach($domains as $id) {
			try {
				$domain = Domain::get_by_ID($id);
				if($domain->get_owner() == $this->oma->authenticated_user
				   || $this->oma->cfg['admins_delete_domains']
				      && in_array($this->oma->authenticated_user, $domain->get_administrators())) {
					if(Domain::delete_by_ID($id)) {
						$deleted[] = $domain->__toString();
					}
				}
			} catch(ObjectNotFoundException $e) {
			}
		}
		if(count($deleted) > 0) {
			$this->ErrorHandler->add_info(sprintf(txt(139), '<ul><li><cite>'.implode('</cite></li><li><cite>', $deleted).'</cite></li></ul>'));
		} else {
			$this->ErrorHandler->add_error(txt(16));
		}
		return count($deleted) > 0;
	}

	protected function change_name(Domain $domain, $newname) {
		if($domain->get_owner() == $this->oma->authenticated_user
				   || $this->oma->cfg['admins_delete_domains']
				      && in_array($this->oma->authenticated_user, $domain->get_administrators())) {
			$oldname = $domain->domain;
			try {
				$domain->immediate_set('domain', $newname);
				Address::replace_in_dest($oldname, $newname);
				RegexpAddress::replace_in_dest($oldname, $newname);
				return true;
			} catch(DuplicateEntryException $e) {
				$this->ErrorHandler->add_error(txt(134));
			}
		} else {
			$this->ErrorHandler->add_error(txt(16));
		}
		return false;
	}

	protected function change_owner(Domain $domain, User $new_owner) {
		if($domain->get_owner() == $this->oma->authenticated_user
		   || $this->oma->cfg['admins_delete_domains']
		      && in_array($this->oma->authenticated_user, $domain->get_administrators())) {
			return $domain->set_owner($new_owner);
		}
		$this->ErrorHandler->add_error(txt(16));
		return false;
	}

	/**
	 * @param	admins		array of IDs
	 */
	protected function change_administrators(Domain $domain, array $admins) {
		if($domain->get_owner() == $this->oma->authenticated_user
		   || $this->oma->cfg['admins_delete_domains']
		      && in_array($this->oma->authenticated_user, $domain->get_administrators())) {
			$domain->purge_admin_list();
			foreach($admins as $id) {
				try {
					$domain->add_administrator(User::get_by_ID($id));
				} catch(ObjectNotFoundException $e) {
				} catch(InvalidArgumentException $e) {
				}
			}
			return true;
		}
		$this->ErrorHandler->add_error(txt(16));
		return false;
	}

	protected function change_categories(Domain $domain, $new_categories) {
		if($domain->get_owner() == $this->oma->authenticated_user
		   || in_array($this->oma->authenticated_user, $domain->get_administrators())) {
			$domain->set_categories($new_categories);
			return true;
		}
		$this->ErrorHandler->add_error(txt(16));
		return false;
	}

	/**
	 * @param	domains		contains IDs.
	 */
	public function change(array $domains, array $change, array $data) {
		if(!$this->oma->validator->validate($data, $change)) {
			return false;
		}
		if(in_array('domain', $change) && count($domains) != 1) {
			$this->ErrorHandler->add_error(txt(91));
			return false;
		}
		foreach($domains as $id) {
			try {
				$domain = Domain::get_by_ID($id);
				if(in_array('domain', $change) && count($domains) == 1) {
					$this->change_name($domain, $data['domain']);
				}
				if(in_array('categories', $change)) {
					$this->change_categories($domain, $data['categories']);
				}
				if(in_array('a_admin', $change)) {
					$this->change_administrators($domain, $data['a_admin']);
				}
				if(in_array('owner', $change)) {
					$this->change_owner($domain, User::get_by_ID($data['owner']));
				}
			} catch(ObjectNotFoundException $e) {
			}
		}
	}

}
?>