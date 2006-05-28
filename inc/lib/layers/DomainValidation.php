<?php
/**
 * This layer's purpose is to make sure that input is usable.
 */
class DomainValidation
	extends DomainCoreDecorator
{
	/** Private instance of InputValidatorSuite. */
	private		$validator;

	public function __construct(DomainManager $decorated) {
		global $cfg;
		parent::__construct($decorated);
		$this->validator	= new InputValidatorSuite(self::$mgr, $cfg);
	}

	public function add($domain, $props) {
		$props['domain'] = $domain;
		if(!$this->validator->validate($props, array('domain', 'categories', 'owner', 'a_admin'))) {
			return false;
		}

		if(!stristr($props['categories'], 'all'))
			$props['categories'] = 'all,'.$props['categories'];
		if(!stristr($props['a_admin'], self::$mgr->current_user->mbox))
			$props['a_admin'] .= ','.self::$mgr->current_user->mbox;

		return $this->inner->add($domain, $props);
	}

	public function remove($domains) {
		// We need the old domain name later...
		if(is_array($domains) && count($domains) > 0) {
			if($this->cfg['admins_delete_domains']) {
				$result = $this->db->SelectLimit('SELECT ID, domain'
					.' FROM '.$this->tablenames['domains']
					.' WHERE (owner='.$this->db->qstr($this->authenticated_user->mbox).' OR a_admin LIKE '.$this->db->qstr('%'.$this->authenticated_user->mbox.'%').') AND '.db_find_in_set($this->db, 'ID', $domains),
					count($domains));
			} else {
				$result = $this->db->SelectLimit('SELECT ID, domain'
					.' FROM '.$this->tablenames['domains']
					.' WHERE owner='.$this->db->qstr($this->authenticated_user->mbox).' AND '.db_find_in_set($this->db, 'ID', $domains),
					count($domains));
			}
			if(!$result === false) {
				while(!$result->EOF) {
					$del_ID[] = $result->fields['ID'];
					$del_nm[] = $result->fields['domain'];
					$result->MoveNext();
				}
				if(isset($del_ID)) {
					$this->db->Execute('DELETE FROM '.$this->tablenames['domains'].' WHERE '.db_find_in_set($this->db, 'ID', $del_ID));
					if($this->db->Affected_Rows() < 1) {
						if($this->db->ErrorNo() != 0) {
							$this->ErrorHandler->add_error($this->db->ErrorMsg());
						}
					} else {
						$this->ErrorHandler->add_info(txt('52').'<br />'.implode(', ', $del_nm));
						// We better deactivate all aliases containing that domain, so users can see the domain was deleted.
						foreach($del_nm as $domainname) {
							$this->db->Execute('UPDATE '.$this->tablenames['virtual'].' SET active = 0, neu = 1 WHERE address LIKE '.$this->db->qstr('%'.$domainname));
						}
						// We can't do such on REGEXP addresses: They may catch more than the given domains.
						$this->user_invalidate_domain_sets();
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

	public function change($domains, $change, $data) {
		$toc = array();		// to be changed

		if(!$this->validator->validate($data, $change)) {
			return false;
		}

		if(!is_array($change)) {
			$this->ErrorHandler->add_error(txt('53'));
			return false;
		}
		if($this->cfg['admins_delete_domains'] && in_array('owner', $change))
			$toc[] = 'owner='.$this->db->qstr($data['owner']);
		if(in_array('a_admin', $change))
			$toc[] = 'a_admin='.$this->db->qstr($data['a_admin']);
		if(in_array('categories', $change))
			$toc[] = 'categories='.$this->db->qstr($data['categories']);
		if(count($toc) > 0) {
			$this->db->Execute('UPDATE '.$this->tablenames['domains']
				.' SET '.implode(',', $toc)
				.' WHERE (owner='.$this->db->qstr($this->authenticated_user->mbox).' or a_admin LIKE '.$this->db->qstr('%'.$this->authenticated_user->mbox.'%').') AND '.db_find_in_set($this->db, 'ID', $domains));
			if($this->db->Affected_Rows() < 1) {
				if($this->db->ErrorNo() != 0) {
					$this->ErrorHandler->add_error($this->db->ErrorMsg());
				} else {
					$this->ErrorHandler->add_error(txt('16'));
				}
			}
		}
		// changing ownership if $this->cfg['admins_delete_domains'] == false
		if(!$this->cfg['admins_delete_domains'] && in_array('owner', $change)) {
			$this->db->Execute('UPDATE '.$this->tablenames['domains']
				.' SET owner='.$this->db->qstr($data['owner'])
				.' WHERE owner='.$this->db->qstr($this->authenticated_user->mbox).' AND '.db_find_in_set($this->db, 'ID', $domains));
		}
		$this->user_invalidate_domain_sets();
		// No domain be renamed?
		if(! in_array('domain', $change)) {
			return true;
		}
		// Otherwise (and if only one) try adapting older addresses.
		if(count($domains) == 1) {
			// Grep the old name, we will need it later for replacement.
			$domain = $this->db->GetRow('SELECT ID, domain AS name FROM '.$this->tablenames['domains'].' WHERE ID = '.$this->db->qstr($domains[0]).' AND (owner='.$this->db->qstr($this->authenticated_user->mbox).' or a_admin LIKE '.$this->db->qstr('%'.$this->authenticated_user->mbox.'%').')');
			if(!$domain === false) {
				// First, update the name. (Corresponding field is marked as unique, therefore we will not receive doublettes.)...
				$this->db->Execute('UPDATE '.$this->tablenames['domains'].' SET domain = '.$this->db->qstr($data['domain']).' WHERE ID = '.$domain['ID']);
				// ... and then, change every old address.
				if($this->db->Affected_Rows() == 1) {
					// address
					$this->db->Execute('UPDATE '.$this->tablenames['virtual'].' SET neu = 1, address = REPLACE(address, '.$this->db->qstr('@'.$domain['name']).', '.$this->db->qstr('@'.$data['domain']).') WHERE address LIKE '.$this->db->qstr('%@'.$domain['name']));
					// dest
					$this->db->Execute('UPDATE '.$this->tablenames['virtual'].' SET neu = 1, dest = REPLACE(dest, '.$this->db->qstr('@'.$domain['name']).', '.$this->db->qstr('@'.$data['domain']).') WHERE dest LIKE '.$this->db->qstr('%@'.$domain['name'].'%'));
					$this->db->Execute('UPDATE '.$this->tablenames['virtual_regexp'].' SET neu = 1, dest = REPLACE(dest, '.$this->db->qstr('@'.$domain['name']).', '.$this->db->qstr('@'.$data['domain']).') WHERE dest LIKE '.$this->db->qstr('%@'.$domain['name'].'%'));
					// canonical
					$this->db->Execute('UPDATE '.$this->tablenames['user'].' SET canonical = REPLACE(canonical, '.$this->db->qstr('@'.$domain['name']).', '.$this->db->qstr('@'.$data['domain']).') WHERE canonical LIKE '.$this->db->qstr('%@'.$domain['name']));
				} else {
					$this->ErrorHandler->add_error($this->db->ErrorMsg());
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