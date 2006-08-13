<?php
class MailboxController
	extends AOMAController
	implements INavigationContributor
{
	public function get_navigation_items() {
		if($this->oma->authenticated_user->a_admin_user >= 1 || $this->oma->current_user->get_number_mailboxes() > 0) {
			return	array('link'		=> 'mailboxes.php'.($this->oma->current_user != $this->oma->authenticated_user ? '?cuser='.$this->oma->current_user->ID : ''),
					'caption'	=> txt('79'),
					'active'	=> stristr($_SERVER['PHP_SELF'], 'mailboxes.php'));
		}
		return false;
	}

	public function controller_get_shortname() {
		return 'mailbox';
	}

	/*
	 * This procedure simply executes every command stored in the array.
	 */
	private function rollback($what) {
		if(is_array($what)) {
			foreach($what as $cmd) {
				eval($cmd.';');
			}
		} else {
			eval($what.';');
		}
	}

	/**
	 * @return 	Array 		with all(!) mailbox names as values, except for incative mailboxes and those to be ignored.
	 */
	public function get_all_names() {
		return array_diff($this->oma->db->GetCol('SELECT mbox FROM '.$this->oma->tablenames['user'].' WHERE active = 1'),
					$this->oma->cfg['user_ignore']);
	}

	/*
	 * Returns list with mailboxes the current user can see.
	 * Typically all his patenkinder will show up.
	 * If the current user is at his pages and is superuser, he will see all mailboxes.
	 */
	public function get_list() {
		$mailboxes = array();

		if($this->oma->current_user == $this->oma->authenticated_user
		   && $this->oma->authenticated_user->a_super >= 1) {
			$where_clause = ' WHERE TRUE';
		} else {
			$where_clause = ' WHERE '.db_find_in_set($this->oma->db, 'usr.ID', User::get_descendants_IDs($this->oma->current_user));
		}

		$result = $this->oma->db->SelectLimit('SELECT usr.ID, mbox, person, pate, max_alias, max_regexp, usr.active, last_login AS lastlogin, a_super, a_admin_domains, a_admin_user, '
						.'COUNT(DISTINCT virt.alias) AS num_alias, '
						.'COUNT(DISTINCT rexp.ID) AS num_regexp '
					.'FROM '.$this->oma->tablenames['user'].' usr '
						.'LEFT OUTER JOIN '.$this->oma->tablenames['virtual'].' virt ON (usr.ID=virt.owner) '
						.'LEFT OUTER JOIN '.$this->oma->tablenames['virtual_regexp'].' rexp ON (usr.ID=rexp.owner) '
					.$where_clause.$_SESSION['filter']['str']['mbox']
					.' GROUP BY usr.ID, mbox, person, pate,  max_alias, max_regexp, usr.active, last_login, a_super, a_admin_domains, a_admin_user '
					.'ORDER BY pate, mbox',
					$_SESSION['limit'], $_SESSION['offset']['mbox']);

		if(!$result === false) {
			while(!$result->EOF) {
				if(!in_array($result->fields['mbox'], $this->oma->cfg['user_ignore']))
					$mailboxes[] = $result->fields;
				$result->MoveNext();
			}
		}

		return $mailboxes;
	}

	/**
	 * To be used on page "Mailboxes".
	 *
	 * @return 	Array 		of three Arrays [ID, mbox, person]
	 */
	public function get_selectable_paten(User $whose) {
		$tmp	= array('ID' => array(), 'mbox' => array(), 'person' => array(), );
		foreach($whose->get_all_descendants() as $user) {
			$tmp['ID'][]		= $user->ID;
			$tmp['mbox'][]		= $user->mbox;
			$tmp['person'][]	= $user->person;
		}
		return $tmp;
	}

	/**
	 * Eliminates every mailbox name from $desired_mboxes which is no descendant
	 * of $who. If the authenticated user is superuser, no filtering is done.
	 *
	 * @param	desired_mboxes	Array with IDs.
	 * @return	Array		of IDs.
	 */
	private function filter_manipulable(User $who, array $desired_mboxes) {
		return array_intersect($desired_mboxes, User::get_descendants_IDs($who));
	}

	/*
	 * $props is typically $_POST, as this function selects all the necessary fields
	 * itself.
	 */
	public function create($mboxname, $props) {
		$rollback	= array();

		// Check inputs for sanity and consistency.
		if(!$this->oma->authenticated_user->a_admin_user > 0) {
			$this->ErrorHandler->add_error(txt('16'));
			return false;
		}
		if(in_array($mboxname, $this->oma->cfg['user_ignore'])) {
			$this->ErrorHandler->add_error(sprintf(txt('130'), txt('83')));
			return false;
		}
		if(!$this->oma->validator->validate($props, array('mbox','person','pate','domains','max_alias','max_regexp','a_admin_domains','a_admin_user','a_super','quota'))) {
			return false;
		}

		// check contingents (only if non-superuser)
		if($this->oma->authenticated_user->a_super == 0) {
			// As the current user's contingents will be decreased we have to use his values.
			if($props['max_alias'] > ($this->oma->current_user->max_alias - $this->oma->current_user->get_used_alias())
			   || $props['max_regexp'] > ($this->oma->current_user->max_regexp - $this->oma->user_get_used_regexp($this->oma->current_user->mbox))) {
				$this->ErrorHandler->add_error(txt('66'));
				return false;
			}
			$quota	= $this->oma->imap->get_users_quota($this->oma->current_user->mbox);
			if($quota->is_set && $props['quota']*1024 > $quota->free) {
				$this->ErrorHandler->add_error(txt('65'));
				return false;
			}
		}

		// on success write the new user to database
		$this->oma->db->Execute('INSERT INTO '.$this->oma->tablenames['user'].' (mbox, person, pate, domains, max_alias, max_regexp, created, a_admin_domains, a_admin_user, a_super)'
				.' VALUES (?,?,?,?,?,?,?,?,?,?)',
				array($props['mbox'], $props['person'], $props['pate'], $props['domains'], $props['max_alias'], $props['max_regexp'], time(), $props['a_admin_domains'], $props['a_admin_user'], $props['a_super'])
				);
		if($this->oma->db->Affected_Rows() < 1) {
			$this->ErrorHandler->add_error(txt('92'));
			// Rollback
			$this->rollback($rollback);
			return false;
		}
		$rollback[] = '$this->oma->db->Execute(\'DELETE FROM '.$this->oma->tablenames['user'].' WHERE mbox='.addslashes($this->oma->db->qstr($mboxname)).'\')';

		$tmpu = User::get_by_ID($this->oma->db->Insert_ID());
		$pw = $tmpu->password->set_random($this->oma->cfg['passwd']['min_length'], $this->oma->cfg['passwd']['max_length']);

		// Decrease current users's contingents...
		if($this->oma->authenticated_user->a_super == 0) {
			$rollback[] = '$this->oma->db->Execute(\'UPDATE '.$this->oma->tablenames['user'].' SET max_alias='.$this->oma->current_user->max_alias.', max_regexp='.$this->oma->current_user->max_regexp.' WHERE mbox='.addslashes($this->oma->db->qstr($this->oma->current_user->mbox)).'\')';
			$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['user']
				.' SET max_alias='.($this->oma->current_user->max_alias-intval($props['max_alias'])).', max_regexp='.($this->oma->current_user->max_regexp-intval($props['max_regexp']))
				.' WHERE mbox='.$this->oma->db->qstr($this->oma->current_user->mbox));
		}
		// ... and then create the user on the server.
		$result = $this->oma->imap->createmb($this->oma->imap->format_user($mboxname));
		if(!$result) {
			$this->ErrorHandler->add_error($this->oma->imap->error_msg);
			// Rollback
			$this->rollback($rollback);
			return false;
		} else {
			if(isset($this->oma->cfg['folders']['create_default']) && is_array($this->oma->cfg['folders']['create_default'])) {
				foreach($this->oma->cfg['folders']['create_default'] as $new_folder) {
					$this->oma->imap->createmb($this->oma->imap->format_user($mboxname, $new_folder));
				}
			}
		}
		$rollback[] = '$this->oma->imap->deletemb($this->oma->imap->format_user(\''.$mboxname.'\'))';

		// Decrease the creator's quota...
		$cur_usr_quota	= $this->oma->imap->getquota($this->oma->imap->format_user($this->oma->current_user->mbox));
		if($this->oma->authenticated_user->a_super == 0 && $cur_usr_quota->is_set) {
			$result = $this->oma->imap->setquota($this->oma->imap->format_user($this->oma->current_user->mbox), $cur_usr_quota->max - $props['quota']*1024);
			if(!$result) {
				$this->ErrorHandler->add_error($this->oma->imap->error_msg);
				// Rollback
				$this->rollback($rollback);
				return false;
			}
			$rollback[] = '$this->oma->imap->setquota($this->oma->imap->format_user($this->oma->current_user->mbox), '.$cur_usr_quota->max .'))';
			$this->ErrorHandler->add_info(sprintf(txt('69'), floor(($cur_usr_quota->max - $props['quota']*1024)/1024) ));
		} else {
			$this->ErrorHandler->add_info(txt('71'));
		}

		// ... and set the new user's quota.
		if(is_numeric($props['quota'])) {
			$result = $this->oma->imap->setquota($this->oma->imap->format_user($mboxname), $props['quota']*1024);
			if(!$result) {
				$this->ErrorHandler->add_error($this->oma->imap->error_msg);
				// Rollback
				$this->rollback($rollback);
				return false;
			}
		}
		$this->ErrorHandler->add_info(sprintf(txt('72'), $mboxname, $props['person'], $pw));

		return true;
	}

	/*
	 * $props can be $_POST, as every sutable field from $change is used.
	 */
	public function change($mboxnames, $change, $props) {
		// Ensure sanity of inputs and check requirements.
		if(!$this->oma->authenticated_user->a_admin_user > 0) {
			$this->ErrorHandler->add_error(txt('16'));
			return false;
		}
		if(!$this->oma->validator->validate($props, $change)) {
			return false;
		}
		$mboxnames = $this->filter_manipulable($this->oma->authenticated_user, $mboxnames);
		if(!count($mboxnames) > 0) {
			return false;
		}

		// Create an array holding every property we have to change.
		$to_change	= array();
		foreach(array('person', 'pate', 'domains', 'a_admin_domains', 'a_admin_user', 'a_super')
			as $property) {
			if(in_array($property, $change)) {
				if(is_numeric($props[$property])) {
					$to_change[]	= $property.' = '.$props[$property];
				} else {
					$to_change[]	= $property.'='.$this->oma->db->qstr($props[$property]);
				}
			}
		}

		// Execute the change operation regarding properties in DB.
		if(count($to_change) > 0) {
			$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['user']
				.' SET '.implode(',', $to_change)
				.' WHERE '.db_find_in_set($this->oma->db, 'mbox', $mboxnames));
		}

		// Manipulate contingents (except quota).
		foreach(array('max_alias', 'max_regexp') as $what) {
			if(in_array($what, $change)) {
				$seek_table = $what == 'max_alias'
						? $this->oma->tablenames['virtual']
						: $this->oma->tablenames['virtual_regexp'];
				$to_be_processed = $mboxnames;
				// Select users which use more aliases than allowed in future.
				$result = $this->oma->db->Execute('SELECT COUNT(*) AS consum, owner, person'
						.' FROM '.$seek_table.' s JOIN '.$this->oma->tablenames['user'].' usr ON (s.owner = usr.ID)'
						.' WHERE '.db_find_in_set($this->oma->db, 'usr.ID', $mboxnames)
						.' GROUP BY owner'
						.' HAVING consum > '.$props[$what]);
				if(!$result === false) {
					// We have to skip them.
					$have_skipped = array();
					while(!$result->EOF) {
						$row	= $result->fields;
						$have_skipped[] = $row['owner'];
						if($this->oma->cfg['mboxview_pers']) {
							$tmp[] = '<a href="'.mkSelfRef(array('cuser' => $row['owner'])).'" title="'.$row['owner'].'">'.$row['person'].' ('.$row['consum'].')</a>';
						} else {
							$tmp[] = '<a href="'.mkSelfRef(array('cuser' => $row['owner'])).'" title="'.$row['person'].'">'.$row['owner'].' ('.$row['consum'].')</a>';
						}
						$result->MoveNext();
					}
					if(count($have_skipped) > 0) {
						$this->ErrorHandler->add_error(sprintf(txt('131'),
									$props[$what], $what == 'max_alias' ? txt('88') : txt('89'),
									implode(', ', $tmp)));
						$to_be_processed = array_diff($to_be_processed, $have_skipped);
					}
				}
				if(count($to_be_processed) > 0) {
					// We don't need further checks if a superuser is logged in.
					if($this->oma->authenticated_user->a_super > 0) {
					$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['user']
						.' SET '.$what.'='.$props[$what]
						.' WHERE '.db_find_in_set($this->oma->db, 'mbox', $to_be_processed));
					} else {
						// Now, calculate whether the current user has enough free contingents.
						$has_to_be_free = $this->oma->db->GetOne('SELECT SUM('.$props[$what].'-'.$what.')'
								.' FROM '.$this->oma->tablenames['user']
								.' WHERE '.db_find_in_set($this->oma->db, 'mbox', $to_be_processed));
						if($has_to_be_free <= $this->oma->current_user->get_used_alias()) {
							// If so, set new contingents on the users...
							$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['user']
							.' SET '.$what.'='.$props[$what]
							.' WHERE '.db_find_in_set($this->oma->db, 'mbox', $to_be_processed));
							// ... and add/remove the difference from the current user.
							$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['user']
							.' SET '.$what.'='.$what.'-'.$has_to_be_free
							.' WHERE mbox='.$this->oma->db->qstr($this->oma->current_user->mbox));
						} else {
							// Else, we have to show an error message.
							$this->ErrorHandler->add_error(txt('66'));
						}
					}
				}
			}
		}

		// Change Quota.
		if(in_array('quota', $change)) {
			$add_quota = 0;
			if($this->oma->authenticated_user->a_super == 0) {
				foreach($mboxnames as $user) {
					if($user != '') {
						$quota	= $this->oma->imap->get_users_quota($user);
						if($quota->is_set)
							$add_quota += intval($props['quota'])*1024 - $quota->max;
					}
				}
				$quota	= $this->oma->imap->get_users_quota($this->oma->current_user->mbox);
				if($add_quota != 0 && $quota->is_set) {
					$this->oma->imap->setquota($this->oma->imap->format_user($this->oma->current_user->mbox), $quota->max - $add_quota);
					$this->ErrorHandler->add_info(sprintf(txt('78'), floor(($quota->max - $add_quota)/1024) ));
				}
			}
			reset($mboxnames);
			foreach($mboxnames as $user) {
				if($user != '') {
					$result = $this->oma->imap->setquota($this->oma->imap->format_user($user), intval($props['quota'])*1024);
					if(!$result) {
						$this->ErrorHandler->add_error($this->oma->imap->error_msg);
					}
				}
			}
		}

		// Renaming of (single) user.
		if(in_array('mbox', $change)) {
			if($this->oma->imap->renamemb($this->oma->imap->format_user($mboxnames['0']), $this->oma->imap->format_user($props['mbox']))) {
				$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['user'].' SET mbox='.$this->oma->db->qstr($props['mbox']).' WHERE mbox='.$this->oma->db->qstr($mboxnames['0']));
				$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['domains'].' SET owner='.$this->oma->db->qstr($props['mbox']).' WHERE owner='.$this->oma->db->qstr($mboxnames['0']));
				$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['domains'].' SET a_admin = REPLACE(a_admin, '.$this->oma->db->qstr($mboxnames['0']).', '.$this->oma->db->qstr($props['mbox']).') WHERE a_admin LIKE '.$this->oma->db->qstr('%'.$mboxnames['0'].'%'));
				$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual'].' SET dest=REPLACE(dest, '.$this->oma->db->qstr($mboxnames['0']).', '.$this->oma->db->qstr($props['mbox']).'), neu = 1 WHERE dest REGEXP '.$this->oma->db->qstr($mboxnames['0'].'[^@]{1,}').' OR dest LIKE '.$this->oma->db->qstr('%'.$mboxnames['0']));
				$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual'].' SET owner='.$this->oma->db->qstr($props['mbox']).' WHERE owner='.$this->oma->db->qstr($mboxnames['0']));
				$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual_regexp'].' SET dest=REPLACE(dest, '.$this->oma->db->qstr($mboxnames['0']).', '.$this->oma->db->qstr($props['mbox']).'), neu = 1 WHERE dest REGEXP '.$this->oma->db->qstr($mboxnames['0'].'[^@]{1,}').' OR dest LIKE '.$this->oma->db->qstr('%'.$mboxnames['0']));
				$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual_regexp'].' SET owner='.$this->oma->db->qstr($props['mbox']).' WHERE owner='.$this->oma->db->qstr($mboxnames['0']));
			} else {
				$this->ErrorHandler->add_error($this->oma->imap->error_msg.'<br />'.txt('94'));
			}
		}

		return true;
	}

	/*
	 * If ressources are freed, the current user will get them.
	 */
	public function delete($mbox_IDs) {
		$mbox_IDs = $this->filter_manipulable($this->oma->authenticated_user, $mbox_IDs);

		// Delete the given mailboxes from server.
		$add_quota = 0;			// how many space was actually freed?
		$toadd = 0;
		$processed = array();		// which users did we delete successfully?
		$deleted = array();
		foreach($mbox_IDs as $id) {
			if($id != '') {
				$user = User::get_by_ID($id);
				// We have to sum up all the space which gets freed in case we later want increase the deleter's quota.
				$quota	= $this->oma->imap->get_users_quota($user->mbox);
				if($this->oma->authenticated_user->a_super == 0
				   && $quota->is_set) {
					$toadd = $quota->max;
				}
				$result = $this->oma->imap->deletemb($this->oma->imap->format_user($user->mbox));
				if(!$result) {		// failure
					$this->ErrorHandler->add_error($this->oma->imap->error_msg);
				} else {		// success
					$add_quota += $toadd;
					$processed[] = $id;
					$deleted[] = $user->person;
				}
			}
		}

		// We need not proceed in case no users were deleted.
		if(count($processed) == 0) {
			return false;
		}

		// Now we have to increase the current user's quota.
		$quota	= $this->oma->imap->get_users_quota($this->oma->current_user->mbox);
		if($this->oma->authenticated_user->a_super == 0
		   && $add_quota > 0
		   && $quota->is_set) {
			$this->oma->imap->setquota($this->oma->imap->format_user($this->oma->current_user->mbox), $quota->max + $add_quota);
			$this->ErrorHandler->add_info(sprintf(txt('76'), floor(($quota->max + $add_quota)/1024) ));
		}

		// Calculate how many contingents get freed if we delete the users.
		if($this->oma->authenticated_user->a_super == 0) {
			$result = $this->oma->db->GetRow('SELECT SUM(max_alias) AS nr_alias, SUM(max_regexp) AS nr_regexp'
					.' FROM '.$this->oma->tablenames['user']
					.' WHERE '.db_find_in_set($this->oma->db, 'ID', $processed));
			if(!$result === false) {
				$will_be_free = $result;
			}
		}

		// patenkinder (will be inherited by the one deleting)
		$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['user']
			.' SET pate='.$this->oma->db->qstr($this->oma->current_user->ID)
			.' WHERE '.db_find_in_set($this->oma->db, 'pate', $processed));
		// virtual
		$this->oma->db->Execute('DELETE FROM '.$this->oma->tablenames['virtual'].' WHERE '.db_find_in_set($this->oma->db, 'owner', $processed));
		$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual'].' SET active=0, neu=1 WHERE '.db_find_in_set($this->oma->db, 'dest', $processed));
		// virtual.regexp
		$this->oma->db->Execute('DELETE FROM '.$this->oma->tablenames['virtual_regexp'].' WHERE '.db_find_in_set($this->oma->db, 'owner', $processed));
		$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['virtual_regexp'].' SET active=0, neu=1 WHERE '.db_find_in_set($this->oma->db, 'dest', $processed));
		// domain (if the one to be deleted owns domains, the deletor will inherit them)
		$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['domains'].' SET owner='.$this->oma->db->qstr($this->oma->current_user->ID).' WHERE '.db_find_in_set($this->oma->db, 'owner', $processed));
		// user
		$this->oma->db->Execute('DELETE FROM '.$this->oma->tablenames['user'].' WHERE '.db_find_in_set($this->oma->db, 'ID', $processed));
		if($this->oma->authenticated_user->a_super == 0 && isset($will_be_free['nr_alias'])) {
			$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['user']
			.' SET max_alias='.($this->oma->current_user->max_alias+$will_be_free['nr_alias']).', max_regexp='.($this->oma->current_user->max_regexp+$will_be_free['nr_regexp'])
			.' WHERE ID='.$this->oma->db->qstr($this->oma->current_user->ID));
		}
		$this->ErrorHandler->add_info(sprintf(txt('75'), implode(',', $deleted)));

		return true;
	}

	/*
	 * active <-> inactive
	 */
	public function toggle_active($mbox_IDs) {
		$tobechanged = $this->filter_manipulable($this->oma->current_user, $mbox_IDs);
		if(count($tobechanged) > 0) {
			$this->oma->db->Execute('UPDATE '.$this->oma->tablenames['user']
					.' SET active = NOT active'
					.' WHERE '.db_find_in_set($this->oma->db, 'ID', $tobechanged));
			if($this->oma->db->Affected_Rows() < 1) {
				if($this->oma->db->ErrorNo() != 0) {
					$this->ErrorHandler->add_error($this->oma->db->ErrorMsg());
				}
			} else {
				return true;
			}
		}
		return false;
	}

}
?>